<?php
class Blockchain {
    private $db;
    
    public function __construct($db) {
        $this->db = $db;
    }
    
    public function getChain($limit = 50) {
        $stmt = $this->db->prepare("SELECT * FROM blockchain_blocks ORDER BY block_index DESC LIMIT ?");
        $stmt->execute([$limit]);
        return $stmt->fetchAll();
    }
    
    public function getLastBlock() {
        $stmt = $this->db->prepare("SELECT * FROM blockchain_blocks ORDER BY block_index DESC LIMIT 1");
        $stmt->execute();
        return $stmt->fetch();
    }
    
    public function addTransaction($requestId, $data) {
        $dataJson = json_encode($data, JSON_UNESCAPED_UNICODE);
        $hash = hash('sha256', $dataJson . time() . rand());
        
        $stmt = $this->db->prepare("
            INSERT INTO blockchain_transactions (request_id, transaction_hash, data, created_at) 
            VALUES (?, ?, ?, NOW())
        ");
        $stmt->execute([$requestId, $hash, $dataJson]);
        
        return $hash;
    }
    
    public function createBlock() {
        $lastBlock = $this->getLastBlock();
        $newIndex = $lastBlock ? $lastBlock['block_index'] + 1 : 1;
        
        $stmt = $this->db->prepare("
            SELECT transaction_hash FROM blockchain_transactions 
            WHERE block_id IS NULL 
            ORDER BY id ASC
        ");
        $stmt->execute();
        $pendingTx = $stmt->fetchAll();
        
        if (empty($pendingTx)) {
            return ['success' => false, 'message' => 'Нет транзакций для создания блока'];
        }
        
        $previousHash = $lastBlock ? $lastBlock['block_hash'] : '0';
        $nonce = rand(100000, 999999);
        $timestamp = date('Y-m-d H:i:s');
        
        $merkleRoot = hash('sha256', implode('', array_column($pendingTx, 'transaction_hash')));
        
        $dataForHash = $newIndex . $previousHash . $timestamp . $nonce . $merkleRoot;
        $blockHash = hash('sha256', $dataForHash);
        
        $stmt = $this->db->prepare("
            INSERT INTO blockchain_blocks (
                block_index, previous_hash, merkle_root, 
                timestamp, nonce, block_hash, transactions_count
            ) VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $newIndex, 
            $previousHash, 
            $merkleRoot,
            $timestamp, 
            $nonce, 
            $blockHash,
            count($pendingTx)
        ]);
        
        $blockId = $this->db->lastInsertId();
        
        $stmt = $this->db->prepare("
            UPDATE blockchain_transactions SET block_id = ? WHERE block_id IS NULL
        ");
        $stmt->execute([$blockId]);
        
        return [
            'success' => true,
            'block_index' => $newIndex,
            'block_hash' => $blockHash,
            'nonce' => $nonce,
            'transactions' => count($pendingTx)
        ];
    }
}