<?php

namespace App\Services;

class MerkleTreeService
{
    /**
     * Hitung Merkle root dari array leaf hashes.
     *
     * Standard binary Merkle tree dengan SHA-256.
     * Jika jumlah leaf ganjil, duplikasi leaf terakhir.
     *
     * @param  array  $leaves  Array of hex-encoded hash strings
     * @return string Hex-encoded Merkle root
     */
    public function root(array $leaves): string
    {
        if (empty($leaves)) {
            return hash('sha256', '');
        }

        if (count($leaves) === 1) {
            return $leaves[0];
        }

        // Duplikasi leaf terakhir jika ganjil
        if (count($leaves) % 2 !== 0) {
            $leaves[] = end($leaves);
        }

        $parents = [];
        for ($i = 0; $i < count($leaves); $i += 2) {
            $parents[] = hash('sha256', hex2bin($leaves[$i]) . hex2bin($leaves[$i + 1]));
        }

        return $this->root($parents);
    }
}
