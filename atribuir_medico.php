<?php
session_start();
require_once 'conexao.php';

header('Content-Type: application/json');

// Verifica permissão
if (!isset($_SESSION['tipo_usuario']) || 
    ($_SESSION['tipo_usuario'] !== 'Admin' && $_SESSION['tipo_usuario'] !== 'Profissional')) {
    echo json_encode([
        'success' => false,
        'message' => 'Acesso não autorizado'
    ]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        if (!isset($_POST['paciente_id']) || !isset($_POST['profissional_id'])) {
            throw new Exception('Dados incompletos');
        }

        $paciente_id = intval($_POST['paciente_id']);
        $profissional_id = intval($_POST['profissional_id']);

        // Verifica se já existe um médico atribuído
        $query_check = "SELECT id FROM paciente_profissional WHERE paciente_id = ?";
        $stmt_check = $conn->prepare($query_check);
        $stmt_check->bind_param("i", $paciente_id);
        $stmt_check->execute();
        $result = $stmt_check->get_result();

        if ($result->num_rows > 0) {
            // Se já existe, atualiza
            $query = "UPDATE paciente_profissional SET profissional_id = ? WHERE paciente_id = ?";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("ii", $profissional_id, $paciente_id);
        } else {
            // Se não existe, insere
            $query = "INSERT INTO paciente_profissional (paciente_id, profissional_id) VALUES (?, ?)";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("ii", $paciente_id, $profissional_id);
        }
        
        if ($stmt->execute()) {
            echo json_encode([
                'success' => true,
                'message' => 'Médico atribuído com sucesso'
            ]);
        } else {
            throw new Exception('Erro ao atribuir médico');
        }

    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => 'Erro: ' . $e->getMessage()
        ]);
    }
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Método não permitido'
    ]);
}
?>