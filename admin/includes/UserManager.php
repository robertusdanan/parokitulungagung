<?php
/**
 * admin/includes/UserManager.php
 * Manajemen user admin via Supabase — pengganti Google Sheets
 *
 * Tabel: users
 * Kolom: id | username | email | password_hash | role | permissions | is_active | created_at | created_by | nama
 */

class UserManager
{
    private SupabaseClient $db;
    private string $table = TABLE_USERS;

    public function __construct(SupabaseClient $db)
    {
        $this->db = $db;
    }

    // ── Internal helpers ────────────────────────────────────────────────

    /**
     * Normalisasi row dari Supabase:
     * - permissions: decode JSON string → array
     * - is_active: pastikan string '1'/'0' agar kompatibel dengan auth.php
     */
    private function normalize(array $row): array
    {
        // permissions bisa berupa JSON string atau sudah array (Supabase jsonb)
        if (isset($row['permissions'])) {
            if (is_string($row['permissions'])) {
                $decoded = json_decode($row['permissions'], true);
                $row['permissions'] = is_array($decoded) ? $decoded : [];
            } elseif (!is_array($row['permissions'])) {
                $row['permissions'] = [];
            }
        } else {
            $row['permissions'] = [];
        }

        // Normalisasi is_active ke string '1'/'0'
        // FIX: Gunakan parseBool() agar menangani semua representasi dari Supabase
        // (boolean true/false, integer 1/0, string "1"/"0", "true"/"false", dll)
        if (array_key_exists('is_active', $row)) {
            $row['is_active'] = $this->parseBool($row['is_active']) ? '1' : '0';
        } else {
            $row['is_active'] = '1';
        }

        return $row;
    }

    /**
     * Konversi nilai is_active dari berbagai format → PHP boolean.
     *
     * Menangani:
     *   PHP bool   : true/false
     *   PHP int    : 1/0
     *   JSON string: "1"/"0", "true"/"false", "yes"/"no", "on"/"off"
     *   null       : dianggap false (nonaktif)
     */
    private function parseBool(mixed $val): bool
    {
        if (is_bool($val)) return $val;
        if (is_null($val)) return false;
        $result = filter_var($val, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
        return $result ?? false;
    }

    // ── Public API ──────────────────────────────────────────────────────

    public function findByUsername(string $username): ?array
    {
        $rows = $this->db->readWhere(
            $this->table,
            ['username=ilike.' . $username],
            '',
            '*'
        );
        if (empty($rows)) return null;
        return $this->normalize($rows[0]);
    }

    public function findByEmail(string $email): ?array
    {
        $rows = $this->db->readWhere(
            $this->table,
            ['email=ilike.' . $email],
            '',
            '*'
        );
        if (empty($rows)) return null;
        return $this->normalize($rows[0]);
    }

    public function findById(string $id): ?array
    {
        $rows = $this->db->read($this->table, ['id' => $id]);
        if (empty($rows)) return null;
        return $this->normalize($rows[0]);
    }

    public function getAll(): array
    {
        $rows = $this->db->read($this->table, [], 'created_at.asc');
        return array_map([$this, 'normalize'], $rows);
    }

    public function create(array $data, string $createdBy): array
    {
        if ($this->findByUsername($data['username'])) {
            throw new RuntimeException('Username sudah digunakan.');
        }

        $id   = uniqid('u', true);
        $hash = password_hash($data['password'], PASSWORD_BCRYPT);

        $permissions = $data['permissions'] ?? [];

        $row = [
            'id'            => $id,
            'username'      => $data['username'],
            'email'         => $data['email']  ?? '',
            'password_hash' => $hash,
            'role'          => $data['role']   ?? ROLE_ADMIN,
            'permissions'   => $permissions,
            'is_active'     => true,
            'created_at'    => date('Y-m-d H:i:s'),
            'created_by'    => $createdBy,
            'nama'          => $data['nama']   ?? '',
        ];

        $inserted = $this->db->insert($this->table, $row);

        // FIX: Cek apakah Supabase menyimpan is_active dengan benar.
        // Jika kolom Supabase memiliki DEFAULT false atau ada constraint yang
        // mengabaikan nilai kita, lakukan UPDATE eksplisit untuk memastikannya.
        $storedActive = isset($inserted['is_active']) ? $this->parseBool($inserted['is_active']) : null;
        if ($storedActive === false) {
            try {
                $this->db->update($this->table, 'id', $id, ['is_active' => true]);
            } catch (Throwable $e) {
                error_log('UserManager::create() gagal set is_active=true: ' . $e->getMessage());
            }
        }

        return ['id' => $id, 'username' => $data['username']];
    }

    public function update(string $id, array $data): bool
    {
        $user = $this->findById($id);
        if (!$user) return false;

        $updateData = [];
        if (isset($data['email']))       $updateData['email']       = $data['email'];
        if (isset($data['role']))        $updateData['role']        = $data['role'];
        if (isset($data['permissions'])) $updateData['permissions'] = $data['permissions'];
        if (isset($data['nama']))        $updateData['nama']        = $data['nama'];
        if (!empty($data['password']))   $updateData['password_hash'] = password_hash($data['password'], PASSWORD_BCRYPT);

        // FIX: Konversi is_active dengan parseBool() agar menangani semua format.
        // Sebelumnya hanya menangani true, 1, dan '1'.
        // String "true" (umum dari JavaScript/JSON) sebelumnya menghasilkan false!
        if (array_key_exists('is_active', $data)) {
            $updateData['is_active'] = $this->parseBool($data['is_active']);
        }

        if (empty($updateData)) return true;

        $this->db->update($this->table, 'id', $id, $updateData);
        return true;
    }

    public function delete(string $id): bool
    {
        $user = $this->findById($id);
        if (!$user) return false;
        $this->db->delete($this->table, 'id', $id);
        return true;
    }

    public function verifyLogin(string $username, string $password): ?array
    {
        $u = $this->findByUsername($username);
        if (!$u) return null;
        if (($u['is_active'] ?? '1') !== '1') return null;
        if (!password_verify($password, $u['password_hash'])) return null;
        return $u;
    }

    /**
     * Update profil diri sendiri (username, email, password, nama).
     * Tidak boleh mengubah role, permissions, atau is_active.
     */
    public function updateSelf(string $id, array $data): bool
    {
        $user = $this->findById($id);
        if (!$user) return false;

        $updateData = [];
        if (isset($data['username']) && $data['username'] !== '') {
            $updateData['username'] = $data['username'];
        }
        if (isset($data['email'])) {
            $updateData['email'] = $data['email'];
        }
        if (!empty($data['password'])) {
            $updateData['password_hash'] = password_hash($data['password'], PASSWORD_BCRYPT);
        }
        if (isset($data['nama'])) {
            $updateData['nama'] = $data['nama'];
        }

        if (empty($updateData)) return true;

        $this->db->update($this->table, 'id', $id, $updateData);
        return true;
    }
}