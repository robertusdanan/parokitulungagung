#!/usr/bin/env python3
"""
scripts/recompress_photos_r2.py
─────────────────────────────────────────────────────────────────────────────
Optimasi & Kompres Ulang Foto yang Sudah Terupload di Cloudflare R2:
- Target: Semua foto di bawah prefix `galerifoto/` (atau prefix yang ditentukan)
- Output: WebP kualitas 60, dimensi maksimal 1600px (LANCZOS resampling)
- Aman: Hanya menimpa file jika hasil kompresi lebih kecil dari ukuran aslinya
- Mendukung: Run lokal / server via credentials env atau workflow GitHub Actions
─────────────────────────────────────────────────────────────────────────────
"""

import io
import os
import sys
import time
import argparse
from typing import Optional
from PIL import Image, ImageOps

try:
    import boto3
    from botocore.config import Config
except ImportError:
    print("Error: boto3 tidak ditemukan. Jalankan: pip install boto3 pillow")
    sys.exit(1)


IMAGE_QUALITY = 60
IMAGE_MAX_DIMENSION = 1600
PHOTO_EXTS = ('.jpg', '.jpeg', '.png', '.webp', '.bmp', '.tiff', '.avif')


def get_s3_client(endpoint: str, access_key: str, secret_key: str):
    return boto3.client(
        's3',
        endpoint_url=endpoint.rstrip('/'),
        aws_access_key_id=access_key,
        aws_secret_access_key=secret_key,
        region_name='auto',
        config=Config(s3={'addressing_style': 'path'}, signature_version='s3v4', retries={'max_attempts': 3})
    )


def compress_image_bytes(data: bytes, quality: int = IMAGE_QUALITY, max_dim: int = IMAGE_MAX_DIMENSION) -> Optional[bytes]:
    try:
        img = Image.open(io.BytesIO(data))
        img = ImageOps.exif_transpose(img)

        w, h = img.size
        longest = max(w, h)
        if longest > max_dim:
            scale = max_dim / float(longest)
            new_w = max(1, int(round(w * scale)))
            new_h = max(1, int(round(h * scale)))
            img = img.resize((new_w, new_h), Image.Resampling.LANCZOS)

        # Pertahankan alpha transparansi jika ada RGBA / LA / P
        if img.mode in ('RGBA', 'LA') or (img.mode == 'P' and 'transparency' in img.info):
            img = img.convert('RGBA')
        elif img.mode != 'RGB':
            img = img.convert('RGB')

        out = io.BytesIO()
        img.save(out, format='WEBP', quality=quality, method=6)
        return out.getvalue()
    except Exception as e:
        print(f"  [!] Error processing image: {e}")
        return None


def run_recompression(
    endpoint: str,
    access_key: str,
    secret_key: str,
    bucket: str,
    prefix: str = 'galerifoto/',
    dry_run: bool = False,
    limit: Optional[int] = None
):
    s3 = get_s3_client(endpoint, access_key, secret_key)
    print(f"=== Memulai Optimasi Foto Cloudflare R2 ===")
    print(f"Bucket   : {bucket}")
    print(f"Prefix   : {prefix}")
    print(f"Kualitas : {IMAGE_QUALITY} (WebP)")
    print(f"Maks Dim : {IMAGE_MAX_DIMENSION}px")
    print(f"Dry Run  : {dry_run}")
    print("=" * 45)

    paginator = s3.get_paginator('list_objects_v2')
    pages = paginator.paginate(Bucket=bucket, Prefix=prefix)

    total_scanned = 0
    total_processed = 0
    total_optimized = 0
    total_skipped = 0
    orig_bytes_sum = 0
    new_bytes_sum = 0

    t0 = time.time()

    for page in pages:
        contents = page.get('Contents', [])
        for obj in contents:
            key = obj['Key']
            if key.endswith('/') or key.endswith('.poster.webp'):
                continue

            lower_key = key.lower()
            if not any(lower_key.endswith(ext) for ext in PHOTO_EXTS):
                continue

            total_scanned += 1
            if limit and total_scanned > limit:
                break

            orig_size = obj['Size']
            orig_bytes_sum += orig_size

            print(f"[{total_scanned}] Scanning: {key} ({orig_size / 1024:.1f} KB)")

            try:
                # Download object dari R2
                resp = s3.get_object(Bucket=bucket, Key=key)
                img_data = resp['Body'].read()

                # Kompres ulang ke WebP 60 / 1600px
                compressed = compress_image_bytes(img_data, IMAGE_QUALITY, IMAGE_MAX_DIMENSION)
                if not compressed:
                    print("  -> Gagal didekode, dilewati.")
                    total_skipped += 1
                    new_bytes_sum += orig_size
                    continue

                new_size = len(compressed)

                if new_size < orig_size:
                    saved_kb = (orig_size - new_size) / 1024
                    saved_pct = ((orig_size - new_size) / orig_size) * 100
                    print(f"  ✓ Berhasil dioptimasi: {orig_size / 1024:.1f} KB -> {new_size / 1024:.1f} KB (hemat {saved_kb:.1f} KB / {saved_pct:.1f}%)")

                    if not dry_run:
                        # Upload versi terkompresi kembali ke R2
                        s3.put_object(
                            Bucket=bucket,
                            Key=key,
                            Body=compressed,
                            ContentType='image/webp',
                            CacheControl='public, max-age=31536000, immutable',
                            Metadata={
                                'recompressed-by': 'github-actions-recompress-photos',
                                'quality': str(IMAGE_QUALITY),
                                'max-dim': str(IMAGE_MAX_DIMENSION)
                            }
                        )
                    total_optimized += 1
                    new_bytes_sum += new_size
                else:
                    print(f"  ⊘ Ukuran file asli sudah sangat kecil/optimal ({orig_size / 1024:.1f} KB vs hasil {new_size / 1024:.1f} KB), tidak ditimpa.")
                    total_skipped += 1
                    new_bytes_sum += orig_size

                total_processed += 1

            except Exception as ex:
                print(f"  [X] Gagal memproses: {ex}")
                total_skipped += 1
                new_bytes_sum += orig_size

        if limit and total_scanned >= limit:
            break

    duration = time.time() - t0
    saved_total_mb = max(0, (orig_bytes_sum - new_bytes_sum)) / (1024 * 1024)

    print("\n" + "=" * 45)
    print("=== RINGKASAN OPTIMASI FOTO R2 ===")
    print(f"Total Foto Diperiksa : {total_scanned}")
    print(f"Foto Dioptimasi      : {total_optimized}")
    print(f"Foto Sudah Optimal   : {total_skipped}")
    print(f"Ukuran Awal          : {orig_bytes_sum / (1024 * 1024):.2f} MB")
    print(f"Ukuran Akhir         : {new_bytes_sum / (1024 * 1024):.2f} MB")
    print(f"Total Ruang Hemat    : {saved_total_mb:.2f} MB")
    print(f"Waktu Eksekusi       : {duration:.1f} detik")
    print("=" * 45)


if __name__ == '__main__':
    parser = argparse.ArgumentParser(description="Optimasi dan kompres ulang foto di Cloudflare R2")
    parser.add_argument('--prefix', default=os.getenv('R2_PREFIX', 'galerifoto/'), help='Prefix path di R2 (default: galerifoto/)')
    parser.add_argument('--dry-run', action='store_true', help='Simulasi tanpa menimpa file di R2')
    parser.add_argument('--limit', type=int, default=None, help='Batasi jumlah foto untuk pengujian')

    args = parser.parse_args()

    endpoint = os.getenv('R2_ENDPOINT_URL')
    access_key = os.getenv('R2_ACCESS_KEY_ID')
    secret_key = os.getenv('R2_SECRET_ACCESS_KEY')
    bucket = os.getenv('R2_BUCKET_NAME')

    # Jika env var kosong, coba ambil dari secrets.php lokal jika ada
    if not (endpoint and access_key and secret_key and bucket):
        try:
            import subprocess
            import json
            php_helper = """
            require_once "/workspace/parokitulungagung/includes/config.php";
            require_once "/workspace/parokitulungagung/includes/functions.php";
            echo json_encode([
                "endpoint" => SECRET_R2_ENDPOINT,
                "access_key" => SECRET_R2_ACCESS_KEY_WRITE,
                "secret_key" => SECRET_R2_SECRET_KEY_WRITE,
                "bucket" => SECRET_R2_BUCKET
            ]);
            """
            out = subprocess.check_output(['php', '-r', php_helper])
            s = json.loads(out.decode('utf-8'))
            endpoint = endpoint or s.get('endpoint')
            access_key = access_key or s.get('access_key')
            secret_key = secret_key or s.get('secret_key')
            bucket = bucket or s.get('bucket')
        except Exception:
            pass

    if not (endpoint and access_key and secret_key and bucket):
        print("Error: Kredensial R2 belum diisi di environment variables (R2_ENDPOINT_URL, R2_ACCESS_KEY_ID, R2_SECRET_ACCESS_KEY, R2_BUCKET_NAME)")
        sys.exit(1)

    run_recompression(
        endpoint=endpoint,
        access_key=access_key,
        secret_key=secret_key,
        bucket=bucket,
        prefix=args.prefix,
        dry_run=args.dry_run or (os.getenv('DRY_RUN', 'false').lower() == 'true'),
        limit=args.limit
    )
