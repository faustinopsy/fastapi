<?php
namespace Fast\Api\Controllers;

use Fast\Api\Models\User;
use Fast\Api\Repositories\UserRepository;

class UserController {
    private $userRepository;

    public function __construct() {
        $this->userRepository = new UserRepository();
    }

    /**
     * @GET("/users")
     */
    public function getAllUsers() {
        $users = $this->userRepository->getAllUsers();
        http_response_code(200);
        echo json_encode($users);
    }

    /**
     * @GET("/users/{id}")
     */
    public function getUserById($id) {
        $user = $this->userRepository->getUserById($id);
        if ($user) {
            http_response_code(200);
            echo json_encode($user);
        } else {
            http_response_code(404);
            echo json_encode(['status' => false, 'message' => 'Usuário não encontrado']);
        }
    }
 /**
     * @GET("/users/nome/{nome}")
     */
    public function getUserByName($nome) {
        $user = $this->userRepository->getUserByName($nome);
        if ($user) {
            http_response_code(200);
            echo json_encode($user);
        } else {
            http_response_code(404);
            echo json_encode(['status' => false, 'message' => 'Usuário não encontrado']);
        }
    }
    /**
     * @POST("/users")
     */
    public function createUser() {
        $input = json_decode(file_get_contents('php://input'), true);
        if ($this->userRepository->getUsuarioByEmail($input['email'])) {
            echo json_encode(['status' => false, 'message' => 'Usuário já existe']);
            exit;
        }
        $user = new User();
        $user->setNome($input['nome']);
        $user->setEmail($input['email']);
        $user->setSenha($input['senha']);
        $createdUser = $this->userRepository->createUser($user);
        if ($createdUser) {
            http_response_code(201);
            echo json_encode(['status' => true, 'message' => 'Usuário criado']);
        }
    }

    /**
     * @PUT("/users/{id}")
     */
    public function updateUser($id) {
        $input = json_decode(file_get_contents('php://input'), true);

        $existingUser = $this->userRepository->getUserById($id);
        if ($existingUser) {
            $user = new User();
            $user->setUsuarioId($id);
            $user->setNome($input['nome'] ?? $existingUser->getNome());
            $user->setEmail($input['email'] ?? $existingUser->getEmail());
            $user->setSenha($input['senha'] ?? $existingUser->getSenha());

            $updatedUser = $this->userRepository->updateUser($user);
            if ($updatedUser) {
                http_response_code(200);
                echo json_encode(['status' => true, 'message' => 'Usuário atualizado']);
            }
        } else {
            http_response_code(404);
            echo json_encode(['status' => false, 'message' => 'Usuário não encontrado']);
        }
    }

    /**
     * @DELETE("/users/{id}")
     */
    public function deleteUser($id) {
        if ($this->userRepository->deleteUser($id)) {
            http_response_code(200);
            echo json_encode(['status' => true, 'message' => 'Usuário excluído']);
        } else {
            http_response_code(404);
            echo json_encode(['status' => false, 'message' => 'Usuário não encontrado']);
        }
    }

    /**
     * @GET("/users/data/{dataini}/{datafim}")
     */
    public function getUsersByDateRange($dataini, $datafim) {
        // Exemplo de implementação
        http_response_code(200);
        echo json_encode(['dataInicial' => $dataini, 'dataFinal' => $datafim]);
    }
}
