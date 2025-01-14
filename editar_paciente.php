<?php
session_start();

include "conexao.php";
include "sidebar.php";

$paciente_id = $_GET['id'];

$sql = "SELECT 
    u.*,
    p.*,
    COALESCE(up.nome, 'Não atribuído') as nome_profissional,
    COALESCE(pr.especialidade, '') as especialidade,
    COALESCE(pr.registro_profissional, '') as registro_profissional,
    COALESCE(pr.unidade_saude, '') as unidade_saude
    FROM usuarios u 
    INNER JOIN pacientes p ON u.id = p.usuario_id 
    LEFT JOIN paciente_profissional pp ON p.id = pp.paciente_id
    LEFT JOIN profissionais pr ON pr.id = pp.profissional_id
    LEFT JOIN usuarios up ON pr.usuario_id = up.id
    WHERE p.id = ?";

$stmt = $conn->prepare($sql);
$stmt->bind_param('i', $paciente_id);
$stmt->execute();
$resultado = $stmt->get_result();
$paciente = $resultado->fetch_assoc();

// Adicionar verificação após buscar os dados
if (!$paciente) {
    echo "<div class='alert alert-danger'>Paciente não encontrado.</div>";
    exit();
}

// Função para verificar permissões
function temPermissao() {
    return isset($_SESSION['tipo_usuario']) && 
           ($_SESSION['tipo_usuario'] === 'Admin' || $_SESSION['tipo_usuario'] === 'Profissional');
}

?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Paciente</title>
    <style>
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .section-card {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            padding: 20px;
            margin-bottom: 30px;
        }

        .header-container {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 1px solid #eee;
        }

        .header-container h1 {
            margin: 0;
        }

        .btn {
            padding: 8px 16px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
        }
        
        .btn-voltar {
            background-color: #6c757d;
            color: white;
        }

        .btn-voltar:hover {
            background-color: #5a6268;
        }
        
        .btn-primary {
            background-color: #4CAF50;
            color: white;
        }
        
        .btn-secondary {
            background-color: #2196F3;
            color: white;
        }

        .btn:disabled {
            background-color: #cccccc;
            cursor: not-allowed;
        }
        
        .section-header {
            margin-bottom: 20px;
            color: #333;
        }

        /* Estilos para as tabelas */
        .data-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10px;
        }

        .data-table th {
            background-color: #f8f9fa;
            font-weight: 600;
            text-align: left;
            padding: 12px;
            border-bottom: 2px solid #dee2e6;
            color: #495057;
        }

        .data-table td {
            padding: 12px;
            border-bottom: 1px solid #dee2e6;
            vertical-align: middle;
        }

        .data-table tr:hover {
            background-color: #f5f5f5;
        }

        /* Status badges */
        .status-badge {
            padding: 6px 12px;
            border-radius: 4px;
            font-size: 14px;
            font-weight: 500;
        }

        .status-cadastrado {
            background-color: #e8f5e9;
            color: #2e7d32;
        }

        .status-pendente {
            background-color: #fff3e0;
            color: #ef6c00;
        }

        /* Info badges */
        .info-badge {
            background-color: #e3f2fd;
            color: #1565c0;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 14px;
        }

        /* Modal Base */
        .modal {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 1000;
        }

        .modal.hidden {
            display: none;
        }

        /* Overlay escuro */
        .modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            backdrop-filter: blur(2px);
        }

        /* Container do Modal */
        .modal-content {
            position: relative;
            background: white;
            width: 90%;
            max-width: 600px;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15);
            z-index: 1001;
            overflow: hidden;
            animation: modalFadeIn 0.3s ease-out;
        }

        /* Cabeçalho do Modal */
        .modal-header {
            padding: 20px 25px;
            background-color: #f8f9fa;
            border-bottom: 1px solid #eee;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .modal-header h2 {
            margin: 0;
            font-size: 1.5rem;
            color: #2c3e50;
            font-weight: 600;
        }

        /* Botão Fechar */
        .close-button {
            background: none;
            border: none;
            font-size: 1.8rem;
            color: #666;
            cursor: pointer;
            padding: 0;
            width: 30px;
            height: 30px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            transition: background-color 0.2s;
        }

        .close-button:hover {
            background-color: #eee;
            color: #333;
        }

        /* Corpo do Modal */
        .modal-body {
            padding: 25px;
            max-height: 60vh;
            overflow-y: auto;
        }

        /* Lista de Médicos */
        .medicos-list {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .medicos-list li {
            padding: 15px;
            border-bottom: 1px solid #eee;
            display: flex;
            justify-content: space-between;
            align-items: center;
            transition: background-color 0.2s;
        }

        .medicos-list li:last-child {
            border-bottom: none;
        }

        .medicos-list li:hover {
            background-color: #f8f9fa;
        }

        /* Botões na lista */
        .medicos-list button {
            padding: 8px 16px;
            background-color: #4CAF50;
            color: white;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 0.9rem;
            transition: background-color 0.2s;
        }

        .medicos-list button:hover {
            background-color: #45a049;
        }

        /* Rodapé do Modal */
        .modal-footer {
            padding: 20px 25px;
            border-top: 1px solid #eee;
            display: flex;
            justify-content: flex-end;
            gap: 10px;
        }

        /* Botões do Rodapé */
        .btn-secondary {
            padding: 8px 16px;
            background-color: #6c757d;
            color: white;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 0.9rem;
            transition: background-color 0.2s;
        }

        .btn-secondary:hover {
            background-color: #5a6268;
        }

        /* Animação de entrada do modal */
        @keyframes modalFadeIn {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Estilização da barra de rolagem */
        .modal-body::-webkit-scrollbar {
            width: 8px;
        }

        .modal-body::-webkit-scrollbar-track {
            background: #f1f1f1;
        }

        .modal-body::-webkit-scrollbar-thumb {
            background: #888;
            border-radius: 4px;
        }

        .modal-body::-webkit-scrollbar-thumb:hover {
            background: #555;
        }

        /* Estilo para o editar médico  */
        .medico-atual {
            background-color: #f8f9fa;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 20px;
        }

        .medico-atual h3 {
            margin: 0 0 10px 0;
            color: #2c3e50;
            font-size: 1.1rem;
        }

        .info-medico {
            color: #666;
            font-size: 0.95rem;
        }

        .separador {
            position: relative;
            text-align: center;
            margin: 25px 0;
        }

        .separador::before {
            content: '';
            position: absolute;
            left: 0;
            top: 50%;
            width: 100%;
            height: 1px;
            background-color: #eee;
        }

        .separador span {
            position: relative;
            background-color: white;
            padding: 0 15px;
            color: #666;
            font-size: 0.9rem;
        }

        /* Estilo para os botões na mesma célula */
        .btn-group {
            display: flex;
            gap: 10px;
        }

        .btn-edit {
            background-color: #2196F3;
        }

        .btn-edit:hover {
            background-color: #1976D2;
        }
    </style>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery.mask/1.14.16/jquery.mask.min.js"></script>
</head>
<body>
    <div class="container">
        <!-- Header com nome do paciente e botão voltar -->
        <div class="header-container">
            <h1>Paciente <?php echo htmlspecialchars($paciente['nome']); ?></h1>
        </div>
        <input type="hidden" id="p_id" value="<?php echo $paciente_id; ?>">

        <!-- Seção de Doença -->
        <div class="section-card">
            <h2 class="section-header">Tipo de Doença</h2>
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Status</th>
                        <th>Tipo</th>
                        <th>Histórico Familiar</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>
                            <?php if ($paciente['tipo_doenca']): ?>
                                <span class="status-badge status-cadastrado">Cadastrado</span>
                            <?php else: ?>
                                <span class="status-badge status-pendente">Pendente</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php echo $paciente['tipo_doenca'] ? htmlspecialchars($paciente['tipo_doenca']) : 'Não cadastrado'; ?>
                        </td>
                        <td>
                            <?php echo $paciente['historico_familiar'] ? htmlspecialchars($paciente['historico_familiar']) : 'Não informado'; ?>
                        </td>
                        <td>
                            <?php if ($paciente['tipo_doenca']): ?>
                                <a href="atualizar_pacientes_doenca.php?id=<?php echo $paciente['usuario_id']; ?>" 
                                   class="btn btn-secondary">Editar</a>
                            <?php else: ?>
                                <a href="cadastro_pacientes_doenca.php?id=<?php echo $paciente['usuario_id']; ?>" 
                                   class="btn btn-primary">Cadastrar</a>
                            <?php endif; ?>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Seção de Médico Responsável -->
        <div class="section-card">
            <h2 class="section-header">Médico Responsável</h2>
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Status</th>
                        <th>Nome do Médico</th>
                        <th>Especialidade</th>
                        <th>Unidade de Saúde</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>
                            <?php if (isset($paciente['nome_profissional']) && $paciente['nome_profissional'] !== 'Não atribuído'): ?>
                                <span class="status-badge status-cadastrado">Atribuído</span>
                            <?php else: ?>
                                <span class="status-badge status-pendente">Não Atribuído</span>
                            <?php endif; ?>
                        </td>
                        <td><?php echo htmlspecialchars($paciente['nome_profissional']); ?></td>
                        <td>
                            <?php echo $paciente['especialidade'] ? htmlspecialchars($paciente['especialidade']) : 'Não informado'; ?>
                        </td>
                        <td>
                            <?php echo $paciente['unidade_saude'] ? htmlspecialchars($paciente['unidade_saude']) : 'Não informado'; ?>
                        </td>
                        <td>
                            <?php if ($paciente['nome_profissional'] !== 'Não atribuído'): ?>
                                <?php if (temPermissao()): ?>
                                    <div class="section-actions">
                                        <button onclick="abrirModalMedico(<?php echo $paciente_id; ?>)" class="btn btn-secondary">
                                            Trocar Médico
                                        </button>
                                    </div>
                                <?php endif; ?>
                            <?php else: ?>
                                <?php if (temPermissao()): ?>
                                    <button onclick="abrirModalAtribuirMedico(<?php echo $paciente_id; ?>)" 
                                            class="btn btn-primary"
                                            <?php echo empty($paciente['tipo_doenca']) ? 'disabled' : ''; ?>>
                                        Atribuir Médico
                                    </button>
                                <?php endif; ?>
                            <?php endif; ?>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Seção de Consultas e Acompanhamento -->
        <div class="section-card">
            <h2 class="section-header">Consultas e Acompanhamento</h2>
            <?php if (temPermissao()): ?>
                <div class="section-actions">
                    <button onclick="abrirModalConsulta(<?php echo $paciente_id; ?>)" class="btn btn-primary">
                        Nova Consulta
                    </button>
                </div>
            <?php endif; ?>
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Data</th>
                        <th>Profissional</th>
                        <th>Pressão Arterial</th>
                        <th>Glicemia</th>
                        <th>Peso</th>
                        <th>IMC</th>
                        <?php if (temPermissao()): ?>
                            <th>Ações</th>
                        <?php endif; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $query = "SELECT c.*, 
                             COALESCE(u.nome, 'Não informado') as nome_profissional,
                             p.especialidade,
                             p.unidade_saude
                             FROM consultas c 
                             LEFT JOIN profissionais p ON c.profissional_id = p.id 
                             LEFT JOIN usuarios u ON p.usuario_id = u.id
                             WHERE c.paciente_id = ? 
                             ORDER BY c.data_consulta DESC";
                    
                    $stmt = $conn->prepare($query);
                    $stmt->bind_param('i', $paciente_id);
                    $stmt->execute();
                    $result = $stmt->get_result();

                    while ($consulta = mysqli_fetch_assoc($result)):
                    ?>
                        <tr>
                            <td><?php echo date('d/m/Y', strtotime($consulta['data_consulta'])); ?></td>
                            <td><?php echo htmlspecialchars($consulta['nome_profissional']); ?></td>
                            <td><?php echo htmlspecialchars($consulta['pressao_arterial']); ?></td>
                            <td><?php echo htmlspecialchars($consulta['glicemia']); ?></td>
                            <td><?php echo $consulta['peso'] ? number_format($consulta['peso'], 2) . ' kg' : '-'; ?></td>
                            <td><?php echo $consulta['imc'] ? number_format($consulta['imc'], 1) : '-'; ?></td>
                            <?php if (temPermissao()): ?>
                                <td>
                                    <div class="btn-group">
                                        <button onclick='editarConsulta(<?php echo json_encode($consulta, JSON_HEX_APOS | JSON_HEX_QUOT); ?>)' 
                                                class="btn btn-primary btn-sm me-2">
                                            <i class="fas fa-edit"></i> Editar
                                        </button>
                                        <button onclick="excluirConsulta(<?php echo $consulta['id']; ?>)" 
                                                class="btn btn-danger btn-sm">
                                            <i class="fas fa-trash"></i> Excluir
                                        </button>
                                    </div>
                                </td>
                            <?php endif; ?>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>

        <!-- Seção de Medicamentos -->
        <div class="section-card">
            <h2 class="section-header">Medicamentos</h2>
            <?php if (temPermissao()): ?>
                <div class="section-actions">
                    <button onclick="abrirModalMedicamento(<?php echo $paciente_id; ?>)" class="btn btn-primary">
                        Novo Medicamento
                    </button>
                </div>
            <?php endif; ?>
            
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Medicamento</th>
                        <th>Dosagem</th>
                        <th>Frequência</th>
                        <th>Data Início</th>
                        <th>Data Fim</th>
                        <th>Observações</th>
                        <?php if (temPermissao()): ?>
                            <th>Ações</th>
                        <?php endif; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $query_med = "SELECT * FROM medicamentos WHERE paciente_id = ? ORDER BY data_inicio DESC";
                    $stmt_med = $conn->prepare($query_med);
                    $stmt_med->bind_param('i', $paciente_id);
                    $stmt_med->execute();
                    $result_med = $stmt_med->get_result();

                    while ($medicamento = $result_med->fetch_assoc()):
                    ?>
                        <tr>
                            <td><?php echo htmlspecialchars($medicamento['nome_medicamento']); ?></td>
                            <td><?php echo htmlspecialchars($medicamento['dosagem']); ?></td>
                            <td><?php echo htmlspecialchars($medicamento['frequencia']); ?></td>
                            <td><?php echo date('d/m/Y', strtotime($medicamento['data_inicio'])); ?></td>
                            <td><?php echo $medicamento['data_fim'] ? date('d/m/Y', strtotime($medicamento['data_fim'])) : '-'; ?></td>
                            <td><?php echo htmlspecialchars($medicamento['observacoes']); ?></td>
                            <?php if (temPermissao()): ?>
                                <td>
                                    <div class="btn-group">
                                        <button onclick='editarMedicamento(<?php echo json_encode($medicamento); ?>)' 
                                                class="btn btn-primary btn-sm me-2">
                                            <i class="fas fa-edit"></i> Editar
                                        </button>
                                        <button onclick="excluirMedicamento(<?php echo $medicamento['id']; ?>)" 
                                                class="btn btn-danger btn-sm">
                                            <i class="fas fa-trash"></i> Excluir
                                        </button>
                                    </div>
                                </td>
                            <?php endif; ?>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>

        <!-- Seção de Histórico de Acompanhamento -->
        <div class="section-card">
            <h2 class="section-header">Histórico de Acompanhamento</h2>
            <?php if (temPermissao()): ?>
                <div class="section-actions">
                    <button onclick="abrirModalAcompanhamento(<?php echo $paciente_id; ?>)" class="btn btn-primary">
                        Novo Acompanhamento
                    </button>
                </div>
            <?php endif; ?>
            
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Data</th>
                        <th>Pressão Arterial</th>
                        <th>Glicemia</th>
                        <th>Peso</th>
                        <th>IMC</th>
                        <th>Hábitos de Vida</th>
                        <th>Estado Emocional</th>
                        <?php if (temPermissao()): ?>
                            <th>Ações</th>
                        <?php endif; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $query_hist = "SELECT * FROM historico_acompanhamento 
                                  WHERE paciente_id = ? 
                                  ORDER BY data_acompanhamento DESC";
                    $stmt_hist = $conn->prepare($query_hist);
                    $stmt_hist->bind_param('i', $paciente_id);
                    $stmt_hist->execute();
                    $result_hist = $stmt_hist->get_result();

                    while ($historico = $result_hist->fetch_assoc()):
                    ?>
                        <tr>
                            <td><?php echo date('d/m/Y', strtotime($historico['data_acompanhamento'])); ?></td>
                            <td><?php echo htmlspecialchars($historico['pressao_arterial']); ?></td>
                            <td><?php echo htmlspecialchars($historico['glicemia']); ?></td>
                            <td><?php echo $historico['peso'] ? number_format($historico['peso'], 2) . ' kg' : '-'; ?></td>
                            <td><?php echo $historico['imc'] ? number_format($historico['imc'], 1) : '-'; ?></td>
                            <td><?php echo nl2br(htmlspecialchars($historico['habitos_de_vida'])); ?></td>
                            <td><?php echo htmlspecialchars($historico['emocao']); ?></td>
                            <?php if (temPermissao()): ?>
                                <td>
                                    <div class="btn-group">
                                        <button onclick='editarAcompanhamento(<?php echo json_encode($historico); ?>)' 
                                                class="btn btn-primary btn-sm me-2">
                                            <i class="fas fa-edit"></i> Editar
                                        </button>
                                        <button onclick="excluirAcompanhamento(<?php echo $historico['id']; ?>)" 
                                                class="btn btn-danger btn-sm">
                                            <i class="fas fa-trash"></i> Excluir
                                        </button>
                                    </div>
                                </td>
                            <?php endif; ?>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>

        <!-- Modal de Medicamento -->
        <div class="modal fade" id="modalMedicamento" tabindex="-1" aria-labelledby="modalMedicamentoLabel" aria-hidden="true">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="modalMedicamentoLabel">Novo Medicamento</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <form id="formMedicamento" method="POST">
                        <div class="modal-body">
                            <input type="hidden" name="medicamento_id" id="medicamento_id">
                            <input type="hidden" name="paciente_id" value="<?php echo $paciente_id; ?>">
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label>Nome do Medicamento:</label>
                                    <input type="text" name="nome_medicamento" id="nome_medicamento" class="form-control" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label>Dosagem:</label>
                                    <input type="text" name="dosagem" id="dosagem" class="form-control" required>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-4 mb-3">
                                    <label>Frequência:</label>
                                    <input type="text" name="frequencia" id="frequencia" class="form-control" required>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label>Data Início:</label>
                                    <input type="date" name="data_inicio" id="data_inicio" class="form-control" required>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label>Data Fim:</label>
                                    <input type="date" name="data_fim" id="data_fim" class="form-control">
                                </div>
                            </div>

                            <div class="form-group mb-3">
                                <label>Observações:</label>
                                <textarea name="observacoes" id="observacoes" class="form-control" rows="3"></textarea>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                            <button type="submit" class="btn btn-primary">Salvar</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Modal de Acompanhamento -->
        <div class="modal fade" id="modalAcompanhamento" tabindex="-1" aria-labelledby="modalAcompanhamentoLabel" aria-hidden="true">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="modalAcompanhamentoLabel">Novo Acompanhamento</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <form id="formAcompanhamento" method="POST">
                        <div class="modal-body">
                            <input type="hidden" name="acompanhamento_id" id="acompanhamento_id">
                            <input type="hidden" name="paciente_id" value="<?php echo $paciente_id; ?>">
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label>Data:</label>
                                    <input type="date" name="data_acompanhamento" id="data_acompanhamento" class="form-control" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label>Pressão Arterial:</label>
                                    <input type="text" name="pressao_arterial" id="pressao_arterial" 
                                           class="form-control pressao-arterial" placeholder="Ex: 120/80">
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label>Glicemia (mg/dL):</label>
                                    <input type="text" name="glicemia" id="glicemia" 
                                           class="form-control glicemia" placeholder="Ex: 99">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label>Peso (kg):</label>
                                    <input type="text" name="peso" id="peso" 
                                           class="form-control peso" placeholder="Ex: 70.5">
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label>Altura (cm):</label>
                                    <input type="text" name="altura" id="altura" 
                                           class="form-control altura" placeholder="Ex: 170">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label>Estado Emocional:</label>
                                    <select name="emocao" id="emocao" class="form-control">
                                        <option value="">Selecione...</option>
                                        <option value="Calmo">Calmo</option>
                                        <option value="Ansioso">Ansioso</option>
                                        <option value="Estressado">Estressado</option>
                                        <option value="Deprimido">Deprimido</option>
                                        <option value="Irritado">Irritado</option>
                                    </select>
                                </div>
                            </div>

                            <div class="form-group mb-3">
                                <label>Hábitos de Vida:</label>
                                <textarea name="habitos_de_vida" id="habitos_de_vida" class="form-control" rows="4"
                                          placeholder="Descreva os hábitos de vida (exercícios, alimentação, uso de álcool/tabaco, etc)"></textarea>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                            <button type="submit" class="btn btn-primary">Salvar</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

    </div>

    <!-- Modal de Nova Consulta -->
    <div class="modal fade" id="modalConsulta" tabindex="-1" aria-labelledby="modalConsultaLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalConsultaLabel">Nova Consulta</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="formConsulta" method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="paciente_id" value="<?php echo $paciente_id; ?>">
                        
                        <div class="form-group mb-3">
                            <label>Profissional:</label>
                            <select name="profissional_id" class="form-control" required>
                                <option value="">Selecione o profissional</option>
                                <?php
                                $query_prof = "SELECT p.id, u.nome 
                                             FROM profissionais p 
                                             JOIN usuarios u ON p.usuario_id = u.id 
                                             ORDER BY u.nome";
                                $result_prof = $conn->query($query_prof);
                                while($row = $result_prof->fetch_assoc()) {
                                    echo "<option value='{$row['id']}'>{$row['nome']}</option>";
                                }
                                ?>
                            </select>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label>Data da Consulta:</label>
                                <input type="date" name="data_consulta" class="form-control" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label>Pressão Arterial:</label>
                                <input type="text" 
                                       name="pressao_arterial" 
                                       class="form-control pressao-arterial" 
                                       placeholder="Ex: 120/80"
                                       title="Formato: 120/80 (sistólica/diastólica)">
                                <small class="form-text text-muted">Sistólica: 70-200 / Diastólica: 40-130</small>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label>Glicemia:</label>
                                <input type="text" 
                                       name="glicemia" 
                                       class="form-control glicemia" 
                                       placeholder="Ex: 99"
                                       title="Valor entre 20 e 600 mg/dL">
                                <small class="form-text text-muted">Valor entre 20 e 600 mg/dL</small>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label>Peso (kg):</label>
                                <input type="text" 
                                       name="peso" 
                                       class="form-control peso" 
                                       placeholder="Ex: 70.5"
                                       title="Valor entre 0 e 300 kg">
                                <small class="form-text text-muted">Valor entre 0 e 300 kg</small>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label>Altura (cm):</label>
                                <input type="text" 
                                       name="altura" 
                                       class="form-control altura" 
                                       placeholder="Ex: 170"
                                       title="Valor entre 10 e 250 cm">
                                <small class="form-text text-muted">Valor entre 10 e 250 cm</small>
                            </div>
                        </div>

                        <div class="form-group mb-3">
                            <label>Observações:</label>
                            <textarea name="observacoes" class="form-control" rows="4"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary">Salvar Consulta</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal para trocar médico -->
    <div class="modal fade" id="modalMedico" tabindex="-1" aria-labelledby="modalMedicoLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalMedicoLabel">Trocar Médico Responsável</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="formTrocarMedico" method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="paciente_id" id="paciente_id">
                        
                        <div class="form-group mb-3">
                            <label>Selecione o Médico:</label>
                            <select name="profissional_id" class="form-control" required>
                                <option value="">Selecione...</option>
                                <?php
                                $query_medicos = "SELECT p.id, u.nome, p.especialidade 
                                                FROM profissionais p 
                                                JOIN usuarios u ON p.usuario_id = u.id 
                                                ORDER BY u.nome";
                                $result_medicos = $conn->query($query_medicos);
                                while($medico = $result_medicos->fetch_assoc()) {
                                    echo "<option value='{$medico['id']}'>{$medico['nome']} - {$medico['especialidade']}</option>";
                                }
                                ?>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary">Salvar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Adicione o Modal de Edição -->
    <div class="modal fade" id="modalEditarConsulta" tabindex="-1" aria-labelledby="modalEditarConsultaLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalEditarConsultaLabel">Editar Consulta</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="formEditarConsulta" method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="consulta_id" id="edit_consulta_id">
                        <input type="hidden" name="paciente_id" value="<?php echo $paciente_id; ?>">
                        
                        <div class="form-group mb-3">
                            <label>Profissional:</label>
                            <select name="profissional_id" id="edit_profissional_id" class="form-control" required>
                                <option value="">Selecione o profissional</option>
                                <?php
                                $query_prof = "SELECT p.id, u.nome 
                                             FROM profissionais p 
                                             JOIN usuarios u ON p.usuario_id = u.id 
                                             ORDER BY u.nome";
                                $result_prof = $conn->query($query_prof);
                                while($row = $result_prof->fetch_assoc()) {
                                    echo "<option value='{$row['id']}'>{$row['nome']}</option>";
                                }
                                ?>
                            </select>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label>Data da Consulta:</label>
                                <input type="date" name="data_consulta" id="edit_data_consulta" class="form-control" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label>Pressão Arterial:</label>
                                <input type="text" name="pressao_arterial" id="edit_pressao_arterial" class="form-control pressao-arterial" placeholder="Ex: 120/80">
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label>Glicemia:</label>
                                <input type="text" name="glicemia" id="edit_glicemia" class="form-control glicemia" placeholder="Ex: 99">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label>Peso (kg):</label>
                                <input type="text" name="peso" id="edit_peso" class="form-control peso" placeholder="Ex: 70.5">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label>Altura (cm):</label>
                                <input type="text" name="altura" id="edit_altura" class="form-control altura" placeholder="Ex: 170">
                            </div>
                        </div>

                        <div class="form-group mb-3">
                            <label>Observações:</label>
                            <textarea name="observacoes" id="edit_observacoes" class="form-control" rows="4"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary">Salvar Alterações</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal para atribuir médico -->
    <div class="modal fade" id="modalAtribuirMedico" tabindex="-1" aria-labelledby="modalAtribuirMedicoLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalAtribuirMedicoLabel">Atribuir Médico</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="formAtribuirMedico" method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="paciente_id" id="atribuir_paciente_id">
                        
                        <div class="form-group mb-3">
                            <label>Selecione o Médico:</label>
                            <select name="profissional_id" class="form-control" required>
                                <option value="">Selecione...</option>
                                <?php
                                $query_medicos = "SELECT p.id, u.nome, p.especialidade 
                                                FROM profissionais p 
                                                JOIN usuarios u ON p.usuario_id = u.id 
                                                ORDER BY u.nome";
                                $result_medicos = $conn->query($query_medicos);
                                while($medico = $result_medicos->fetch_assoc()) {
                                    echo "<option value='{$medico['id']}'>{$medico['nome']} - {$medico['especialidade']}</option>";
                                }
                                ?>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary">Salvar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        /* Funções para o modal de adicionar médico */
        function abrirModal(pacienteId) {
            // Verifica se o botão está desabilitado
            const button = event.target;
            if (button.disabled) {
                return; // Não faz nada se o botão estiver desabilitado
            }

            const modal = document.getElementById('modalMedicos');
            modal.classList.remove('hidden');

            // Carregar médicos do servidor
            fetch('buscar_medicos.php')
                .then(response => response.json())
                .then(medicos => {
                    const lista = document.getElementById('listaMedicos');
                    lista.innerHTML = ''; // Limpar a lista de médicos

                    medicos.forEach(medico => {
                        const li = document.createElement('li');
                        li.innerHTML = `
                            ${medico.nome} (${medico.especialidade})
                            <button onclick="atribuirMedico(${pacienteId}, ${medico.id})">Selecionar</button>
                        `;
                        lista.appendChild(li);
                    });
                });
        }

        function fecharModal() {
            const modal = document.getElementById('modalMedicos');
            modal.classList.add('hidden');
        }

        function atribuirMedico(pacienteId, medicoId) {
            // Adicionar console.log para debug
            console.log('Atribuindo médico:', { pacienteId, medicoId });
            
            fetch('atribuir_medico.php', {
                method: 'POST',
                headers: { 
                    'Content-Type': 'application/json',
                    // Adicionar header para prevenir cache
                    'Cache-Control': 'no-cache'
                },
                body: JSON.stringify({ pacienteId, medicoId })
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error('Erro na resposta do servidor');
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    alert('Médico atribuído com sucesso!');
                    fecharModal();
                    location.reload();
                } else {
                    alert('Erro ao atribuir médico: ' + (data.message || 'Erro desconhecido'));
                }
            })
            .catch(error => {
                console.error('Erro:', error);
                alert('Erro ao atribuir médico. Verifique o console para mais detalhes.');
            });
        }

        function atualizarListaMedicos(medicos, pacienteId) {
            const lista = document.getElementById('listaMedicos');
            lista.innerHTML = '';

            medicos.forEach(medico => {
                const li = document.createElement('li');
                li.innerHTML = `
                    <div>
                        <strong>${medico.nome}</strong>
                        <div style="color: #666; font-size: 0.9rem; margin-top: 4px;">
                            ${medico.especialidade}
                        </div>
                    </div>
                    <button onclick="atribuirMedico(${pacienteId}, ${medico.id})">
                        Selecionar
                    </button>
                `;
                lista.appendChild(li);
            });
        }

        /* Funções para o modal de editar médico */
        function abrirModalEditar(pacienteId, medicoAtual, especialidadeAtual) {
            const modal = document.getElementById('modalEditarMedico');
            modal.classList.remove('hidden');

            // Preenche informações do médico atual
            const infoMedico = modal.querySelector('.info-medico');
            infoMedico.innerHTML = `
                <p><strong>Nome:</strong> ${medicoAtual || 'Não atribuído'}</p>
                <p><strong>Especialidade:</strong> ${especialidadeAtual || 'Não informada'}</p>
            `;

            // Carrega lista de médicos disponíveis
            fetch('buscar_medicos.php')
                .then(response => response.json())
                .then(medicos => {
                    const lista = document.getElementById('listaMedicosEditar');
                    lista.innerHTML = '';

                    medicos.forEach(medico => {
                        const li = document.createElement('li');
                        li.innerHTML = `
                            <div>
                                <strong>${medico.nome}</strong>
                                <div style="color: #666; font-size: 0.9rem; margin-top: 4px;">
                                    ${medico.especialidade}
                                </div>
                            </div>
                            <button onclick="atribuirMedico(${pacienteId}, ${medico.id})">
                                Selecionar
                            </button>
                        `;
                        lista.appendChild(li);
                    });
                });
        }

        function fecharModalEditar() {
            const modal = document.getElementById('modalEditarMedico');
            modal.classList.add('hidden');
        }

        function atualizarMedico(pacienteId, medicoId) {
            fetch('atribuir_medico.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ pacienteId, medicoId }),
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Médico atualizado com sucesso!');
                    fecharModalEditar();
                    location.reload();
                } else {
                    alert('Erro ao atualizar médico.');
                }
            });
        }

        function abrirModalConsulta(pacienteId) {
            var myModal = new bootstrap.Modal(document.getElementById('modalConsulta'));
            myModal.show();
        }

        function verDetalhesConsulta(consultaId) {
            // Fazer uma requisição AJAX para buscar os detalhes da consulta
            $.ajax({
                url: 'buscar_consulta.php',
                type: 'GET',
                data: { id: consultaId },
                success: function(response) {
                    // Aqui você pode criar outro modal para mostrar os detalhes completos
                    // incluindo as observações
                    $('#modalDetalhesConsulta').html(response).modal('show');
                }
            });
        }

        // Adicione este código para fechar o modal após submeter o formulário com sucesso
        $(document).ready(function() {
            $('#formConsulta').on('submit', function(e) {
                e.preventDefault();
                
                // Validar pressão arterial
                const pressaoArterial = $('input[name="pressao_arterial"]').val();
                if (pressaoArterial) {
                    const pattern = /^\d{2,3}\/\d{2,3}$/;
                    if (!pattern.test(pressaoArterial)) {
                        alert('Formato de pressão arterial inválido. Use o formato: 120/80');
                        return false;
                    }
                    
                    const [sistolica, diastolica] = pressaoArterial.split('/').map(Number);
                    if (sistolica < 70 || sistolica > 200 || diastolica < 40 || diastolica > 130) {
                        alert('Valores de pressão arterial fora do intervalo aceitável');
                        return false;
                    }
                }
                
                // Validar glicemia
                const glicemia = $('input[name="glicemia"]').val();
                if (glicemia && (glicemia < 20 || glicemia > 600)) {
                    alert('Valor de glicemia fora do intervalo aceitável (20-600 mg/dL)');
                    return false;
                }
                
                // Validar peso
                const peso = $('input[name="peso"]').val();
                if (peso && (peso < 20 || peso > 300)) {
                    alert('Valor de peso fora do intervalo aceitável (20-300 kg)');
                    return false;
                }
                
                // Validar altura
                const altura = $('input[name="altura"]').val();
                if (altura && (altura < 10 || altura > 250)) {
                    alert('Valor de altura fora do intervalo aceitável (10-250 cm)');
                    return false;
                }
                
                // Se todas as validações passarem, envia o formulário
                $.ajax({
                    url: 'salvar_consulta.php',
                    type: 'POST',
                    data: $(this).serialize(),
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            alert('Consulta cadastrada com sucesso!');
                            var myModal = bootstrap.Modal.getInstance(document.getElementById('modalConsulta'));
                            myModal.hide();
                            location.reload();
                        } else {
                            alert(response.message || 'Erro ao cadastrar consulta');
                        }
                    },
                    error: function() {
                        alert('Erro ao processar a requisição');
                    }
                });
            });
        });

        function validarPressaoArterial(input) {
            // Remove qualquer caractere que não seja número ou /
            input.value = input.value.replace(/[^\d/]/g, '');
            
            if (input.value.includes('/')) {
                let [sistolica, diastolica] = input.value.split('/').map(Number);
                
                // Limita sistólica entre 70 e 200
                if (sistolica && !isNaN(sistolica)) {
                    sistolica = Math.min(Math.max(parseInt(sistolica), 70), 200);
                }
                
                // Limita diastólica entre 40 e 130
                if (diastolica && !isNaN(diastolica)) {
                    diastolica = Math.min(Math.max(parseInt(diastolica), 40), 130);
                }
                
                // Atualiza o valor do input
                if (sistolica && diastolica) {
                    input.value = `${sistolica}/${diastolica}`;
                }
            }
        }

        $(document).ready(function() {
            // Máscara para pressão arterial (000/000)
            $('.pressao-arterial').mask('000/000');
            
            // Máscara para glicemia (até 3 dígitos)
            $('.glicemia').mask('000', {
                reverse: true,
                translation: {
                    '0': {pattern: /[0-9]/}
                }
            });
            
            // Máscara para peso (000.0)
            $('.peso').mask('000.0', {
                reverse: true,
                translation: {
                    '0': {pattern: /[0-9]/}
                }
            });
            
            // Máscara para altura (000)
            $('.altura').mask('000', {
                reverse: true,
                translation: {
                    '0': {pattern: /[0-9]/}
                }
            });

            // Validação adicional para pressão arterial
            $('.pressao-arterial').on('blur', function() {
                let valor = $(this).val();
                if (valor) {
                    let [sistolica, diastolica] = valor.split('/').map(Number);
                    if (sistolica < 70 || sistolica > 200 || diastolica < 40 || diastolica > 130) {
                        alert('Valores de pressão arterial fora do intervalo aceitável');
                        $(this).val('');
                    }
                }
            });

            // Validação para glicemia
            $('.glicemia').on('blur', function() {
                let valor = parseInt($(this).val());
                if (valor < 20 || valor > 600) {
                    alert('Valor de glicemia fora do intervalo aceitável (20-600 mg/dL)');
                    $(this).val('');
                }
            });

            // Validação para peso
            $('.peso').on('blur', function() {
                let valor = parseFloat($(this).val());
                if (valor < 0 || valor > 300) {
                    alert('Valor de peso fora do intervalo aceitável (0-300 kg)');
                    $(this).val('');
                }
            });

            // Validação para altura
            $('.altura').on('blur', function() {
                let valor = parseInt($(this).val());
                if (valor < 10 || valor > 250) {
                    alert('Valor de altura fora do intervalo aceitável (10-250 cm)');
                    $(this).val('');
                }
            });
        });

        function editarConsulta(consulta) {
            // Converte a data para o formato correto
            let dataConsulta = consulta.data_consulta;
            if (dataConsulta) {
                // Garante que a data esteja no formato YYYY-MM-DD
                dataConsulta = dataConsulta.split('T')[0];
            }

            // Debug
            console.log('Dados recebidos:', {
                id: consulta.id,
                profissional_id: consulta.profissional_id,
                data: dataConsulta,
                pressao: consulta.pressao_arterial,
                glicemia: consulta.glicemia,
                peso: consulta.peso,
                altura: consulta.altura,
                obs: consulta.observacoes
            });

            // Preenche os campos do modal
            $('#edit_consulta_id').val(consulta.id);
            $('#edit_profissional_id').val(consulta.profissional_id);
            $('#edit_data_consulta').val(dataConsulta);
            $('#edit_pressao_arterial').val(consulta.pressao_arterial);
            $('#edit_glicemia').val(consulta.glicemia);
            $('#edit_peso').val(consulta.peso);
            $('#edit_altura').val(consulta.altura);
            $('#edit_observacoes').val(consulta.observacoes);

            // Abre o modal
            var myModal = new bootstrap.Modal(document.getElementById('modalEditarConsulta'));
            myModal.show();
        }

        // Manipula o envio do formulário de edição
        $('#formEditarConsulta').on('submit', function(e) {
            e.preventDefault();
            
            let formData = $(this).serializeArray();
            let dados = {};
            
            // Converte os dados do formulário para um objeto
            formData.forEach(function(item) {
                dados[item.name] = item.value;
            });

            // Debug
            console.log('Dados a serem enviados:', dados);
            
            $.ajax({
                url: 'atualizar_consulta.php',
                type: 'POST',
                data: dados,
                dataType: 'json',
                success: function(response) {
                    console.log('Resposta do servidor:', response);
                    if (response.success) {
                        alert('Consulta atualizada com sucesso!');
                        var myModal = bootstrap.Modal.getInstance(document.getElementById('modalEditarConsulta'));
                        myModal.hide();
                        location.reload();
                    } else {
                        alert(response.message || 'Erro ao atualizar consulta');
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Erro detalhado:', {
                        status: status,
                        error: error,
                        responseText: xhr.responseText,
                        readyState: xhr.readyState,
                        statusText: xhr.statusText
                    });
                    alert('Erro ao processar a requisição. Verifique o console para mais detalhes.');
                }
            });
        });

        function verDetalhesConsulta(consultaId) {
            // Implementação existente...
        }

        function excluirConsulta(consultaId) {
            if (confirm('Tem certeza que deseja excluir esta consulta?')) {
                $.ajax({
                    url: 'excluir_consulta.php',
                    type: 'POST',
                    data: { consulta_id: consultaId },
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            alert('Consulta excluída com sucesso!');
                            location.reload();
                        } else {
                            alert(response.message || 'Erro ao excluir consulta');
                        }
                    },
                    error: function() {
                        alert('Erro ao processar a requisição');
                    }
                });
            }
        }

        function abrirModalMedico(pacienteId) {
            $('#paciente_id').val(pacienteId);
            var myModal = new bootstrap.Modal(document.getElementById('modalMedico'));
            myModal.show();
        }

        // Manipular o envio do formulário
        $('#formTrocarMedico').on('submit', function(e) {
            e.preventDefault();
            
            $.ajax({
                url: 'trocar_medico.php',
                type: 'POST',
                data: $(this).serialize(),
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        alert('Médico responsável atualizado com sucesso!');
                        var myModal = bootstrap.Modal.getInstance(document.getElementById('modalMedico'));
                        myModal.hide();
                        location.reload();
                    } else {
                        alert(response.message || 'Erro ao atualizar médico responsável');
                    }
                },
                error: function() {
                    alert('Erro ao processar a requisição');
                }
            });
        });

        function abrirModalAtribuirMedico(pacienteId) {
            $('#atribuir_paciente_id').val(pacienteId);
            var myModal = new bootstrap.Modal(document.getElementById('modalAtribuirMedico'));
            myModal.show();
        }

        // Manipular o envio do formulário de atribuir médico
        $('#formAtribuirMedico').on('submit', function(e) {
            e.preventDefault();
            
            $.ajax({
                url: 'atribuir_medico.php',
                type: 'POST',
                data: $(this).serialize(),
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        alert('Médico atribuído com sucesso!');
                        var myModal = bootstrap.Modal.getInstance(document.getElementById('modalAtribuirMedico'));
                        myModal.hide();
                        location.reload();
                    } else {
                        alert(response.message || 'Erro ao atribuir médico');
                    }
                },
                error: function() {
                    alert('Erro ao processar a requisição');
                }
            });
        });

        function abrirModalMedicamento(pacienteId) {
            $('#formMedicamento')[0].reset();
            $('#medicamento_id').val('');
            $('#modalMedicamentoLabel').text('Novo Medicamento');
            var myModal = new bootstrap.Modal(document.getElementById('modalMedicamento'));
            myModal.show();
        }

        function editarMedicamento(medicamento) {
            $('#medicamento_id').val(medicamento.id);
            $('#nome_medicamento').val(medicamento.nome_medicamento);
            $('#dosagem').val(medicamento.dosagem);
            $('#frequencia').val(medicamento.frequencia);
            $('#data_inicio').val(medicamento.data_inicio);
            $('#data_fim').val(medicamento.data_fim);
            $('#observacoes').val(medicamento.observacoes);
            
            $('#modalMedicamentoLabel').text('Editar Medicamento');
            var myModal = new bootstrap.Modal(document.getElementById('modalMedicamento'));
            myModal.show();
        }

        function excluirMedicamento(medicamentoId) {
            if (confirm('Tem certeza que deseja excluir este medicamento?')) {
                $.ajax({
                    url: 'excluir_medicamento.php',
                    type: 'POST',
                    data: { medicamento_id: medicamentoId },
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            alert('Medicamento excluído com sucesso!');
                            location.reload();
                        } else {
                            alert(response.message || 'Erro ao excluir medicamento');
                        }
                    },
                    error: function() {
                        alert('Erro ao processar a requisição');
                    }
                });
            }
        }

        $('#formMedicamento').on('submit', function(e) {
            e.preventDefault();
            
            $.ajax({
                url: 'salvar_medicamento.php',
                type: 'POST',
                data: $(this).serialize(),
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        alert('Medicamento salvo com sucesso!');
                        var myModal = bootstrap.Modal.getInstance(document.getElementById('modalMedicamento'));
                        myModal.hide();
                        location.reload();
                    } else {
                        alert(response.message || 'Erro ao salvar medicamento');
                    }
                },
                error: function() {
                    alert('Erro ao processar a requisição');
                }
            });
        });

        function abrirModalAcompanhamento(pacienteId) {
            // Limpar o formulário
            $('#formAcompanhamento')[0].reset();
            // Definir o ID do paciente
            $('input[name="paciente_id"]').val(pacienteId);
            // Limpar o ID do acompanhamento (é um novo registro)
            $('#acompanhamento_id').val('');
            // Abrir o modal
            $('#modalAcompanhamento').modal('show');
        }

        function editarAcompanhamento(historico) {
            // Preencher o formulário com os dados existentes
            $('#acompanhamento_id').val(historico.id);
            $('#data_acompanhamento').val(historico.data_acompanhamento);
            $('#pressao_arterial').val(historico.pressao_arterial);
            $('#glicemia').val(historico.glicemia);
            $('#peso').val(historico.peso);
            $('#altura').val(historico.altura);
            $('#emocao').val(historico.emocao);
            $('#habitos_de_vida').val(historico.habitos_de_vida);
            
            // Atualizar o título do modal
            $('#modalAcompanhamentoLabel').text('Editar Acompanhamento');
            
            // Abrir o modal
            $('#modalAcompanhamento').modal('show');
        }

        function excluirAcompanhamento(acompanhamentoId) {
            if (confirm('Tem certeza que deseja excluir este acompanhamento?')) {
                $.ajax({
                    url: 'excluir_acompanhamento.php',
                    type: 'POST',
                    data: { acompanhamento_id: acompanhamentoId },
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            alert('Acompanhamento excluído com sucesso!');
                            location.reload();
                        } else {
                            alert(response.message || 'Erro ao excluir acompanhamento');
                        }
                    },
                    error: function() {
                        alert('Erro ao processar a requisição');
                    }
                });
            }
        }

        $(document).ready(function() {
            // Máscaras
            $('.pressao-arterial').mask('000/000');
            
            $('.glicemia').mask('000', {
                reverse: true,
                translation: {
                    '0': {pattern: /[0-9]/}
                }
            });
            
            $('.peso').mask('000.0', {
                reverse: true,
                translation: {
                    '0': {pattern: /[0-9]/}
                }
            });
            
            $('.altura').mask('000', {
                reverse: true,
                translation: {
                    '0': {pattern: /[0-9]/}
                }
            });

            // Validações
            $('.pressao-arterial').on('blur', function() {
                let valor = $(this).val();
                if (valor) {
                    let [sistolica, diastolica] = valor.split('/').map(Number);
                    if (sistolica < 70 || sistolica > 200 || diastolica < 40 || diastolica > 130) {
                        alert('Valores de pressão arterial fora do intervalo aceitável\nSistólica: 70-200 mmHg\nDiastólica: 40-130 mmHg');
                        $(this).val('');
                    }
                }
            });

            $('.glicemia').on('blur', function() {
                let valor = parseInt($(this).val());
                if (valor < 20 || valor > 600) {
                    alert('Valor de glicemia fora do intervalo aceitável (20-600 mg/dL)');
                    $(this).val('');
                }
            });

            $('.peso').on('blur', function() {
                let valor = parseFloat($(this).val());
                if (valor < 0 || valor > 300) {
                    alert('Valor de peso fora do intervalo aceitável (0-300 kg)');
                    $(this).val('');
                }
            });

            $('.altura').on('blur', function() {
                let valor = parseInt($(this).val());
                if (valor < 100 || valor > 250) {
                    alert('Valor de altura fora do intervalo aceitável (100-250 cm)');
                    $(this).val('');
                }
            });

            // Atualizar IMC automaticamente quando peso ou altura mudar
            $('.peso, .altura').on('change', function() {
                calcularIMC();
            });
        });

        function calcularIMC() {
            const peso = parseFloat($('.peso').val());
            const altura = parseFloat($('.altura').val()) / 100; // Converter cm para metros
            
            if (peso && altura) {
                const imc = peso / (altura * altura);
                $('#imc').val(imc.toFixed(1));
                
                // Classificação do IMC
                let classificacao = '';
                if (imc < 18.5) classificacao = 'Abaixo do peso';
                else if (imc < 25) classificacao = 'Peso normal';
                else if (imc < 30) classificacao = 'Sobrepeso';
                else if (imc < 35) classificacao = 'Obesidade Grau I';
                else if (imc < 40) classificacao = 'Obesidade Grau II';
                else classificacao = 'Obesidade Grau III';
                
                $('#classificacao_imc').text(classificacao);
            }
        }

        $(document).ready(function() {
            // Manipular o envio do formulário de acompanhamento
            $('#formAcompanhamento').on('submit', function(e) {
                e.preventDefault();
                
                console.log('Formulário submetido');
                
                // Coletar os dados do formulário
                let formData = new FormData(this);
                
                // Debug dos dados
                for (let pair of formData.entries()) {
                    console.log(pair[0] + ': ' + pair[1]);
                }

                $.ajax({
                    url: 'salvar_acompanhamento.php',
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    dataType: 'json',
                    success: function(response) {
                        console.log('Resposta:', response);
                        if (response.success) {
                            alert('Acompanhamento salvo com sucesso!');
                            $('#modalAcompanhamento').modal('hide');
                            location.reload();
                        } else {
                            alert('Erro ao salvar: ' + response.message);
                        }
                    },
                    error: function(xhr, status, error) {
                        console.log('XHR:', xhr);
                        console.log('Status:', status);
                        console.log('Error:', error);
                        
                        let errorMessage = 'Erro ao processar a requisição.';
                        
                        try {
                            let response = JSON.parse(xhr.responseText);
                            if (response.message) {
                                errorMessage = response.message;
                            }
                        } catch (e) {
                            console.log('Erro ao parsear resposta:', e);
                        }
                        
                        alert('Erro: ' + errorMessage);
                    }
                });
            });
        });
    </script>

</body>
</html>