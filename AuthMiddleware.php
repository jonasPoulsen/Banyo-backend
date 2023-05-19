<?php
require __DIR__ . '/classes/JwtHandler.php';

class Auth extends JwtHandler
{
    protected $db;
    protected $headers;
    protected $token;

    public function __construct($db, $headers)
    {
        parent::__construct();
        $this->db = $db;
        $this->headers = $headers;
    }

    public function isValid($newValues = [])
    {

        if (array_key_exists('Authorization', $this->headers) && preg_match('/Bearer\s(\S+)/', $this->headers['Authorization'], $matches)) {

            $data = $this->jwtDecodeData($matches[1]);
                

                if (
                    isset($data['data']->user_id) &&
                    $user = $this->fetchUser($data['data']->user_id)
                ) :

                    if (
                        array_key_exists('GetUser', $this->headers)
                    ){
                        return [
                            "success" => 1,
                            "user" => $user
                        ];
                    }

                    if (
                        array_key_exists('Update', $this->headers) &&
                        !empty($newValues) &&
                        $this->updateUser($user['id'], $newValues)
                    ){
                        return [
                            "success" => 1,
                            "message" => "User is updated!"
                        ];

                    }

                    if (
                        array_key_exists('AllUsers', $this->headers) &&
                        intval($user['admin']) === 1 && 
                        $allUsers = $this->fetchUsers()
                    ){
                        return [
                            "success" => 1,
                            "users" => $allUsers
                        ];

                    }

                    if (
                        array_key_exists('DeleteUser', $this->headers) &&
                        intval($user['admin']) === 1 && 
                        $this->deleteUser(intval($this->headers['DeleteUser']))
                    ){
                        if ($allUsers = $this->fetchUsers()) {
                            return [
                                "success" => 1,
                                "users" => $allUsers
                            ];
                        }
                        

                    }

                    
                    return [
                        "success" => 0,
                        "message" => "error",
                    ];

                else :
                    return [
                        "success" => 0,
                        "message" => $data['message'],
                    ];
                endif;


        } else {
            return [
                "success" => 0,
                "message" => "Token not found in request"
            ];
        }
    }

    protected function fetchUser($user_id)
    {
        try {
            $fetch_user_by_id = "SELECT `id`,`name`,`email`, `admin` FROM `users` WHERE `id`=:id";
            $query_stmt = $this->db->prepare($fetch_user_by_id);
            $query_stmt->bindValue(':id', $user_id, PDO::PARAM_INT);
            $query_stmt->execute();

            if ($query_stmt->rowCount()) :
                return $query_stmt->fetch(PDO::FETCH_ASSOC);
            else :
                return false;
            endif;
        } catch (PDOException $e) {
            return null;
        }
    }

    protected function fetchUsers()
    {
        try {
            $fetch_all_user = "SELECT `id`,`name`,`email`,`created` FROM `users`";
            $query_stmt = $this->db->prepare($fetch_all_user);
            $query_stmt->execute();

            if ($query_stmt->rowCount()) :
                return $query_stmt->fetchAll(PDO::FETCH_ASSOC);
            else :
                return false;
            endif;
        } catch (PDOException $e) {
            return null;
        }
    }

    protected function deleteUser($id)
    {
        try {
            $delete_user = "DELETE FROM `users` WHERE `id`=:id";
            $query_stmt = $this->db->prepare($delete_user);
            $query_stmt->bindValue(':id', $id, PDO::PARAM_INT);
            $query_stmt->execute();

            if ($query_stmt->rowCount()) :
                //return $query_stmt->fetchAll(PDO::FETCH_ASSOC);
                //return $this->fetchUsers();
                return true;
            else :
                return false;
            endif;
        } catch (PDOException $e) {
            return null;
        }
    }

    protected function updateUser($id, $newValues)
    {
        //$data = $newValues;

        //tjek om felter er tomme
        // hvis ja, så sæt det eksisterende data ind fra $oldValues
        // $name = "";
        // $email ="";
        $name = trim($newValues->name);
        $email = trim($newValues->email);
        // if (!empty($newValues->name)):
        //     $name = trim($newValues->name);
        // else:
        //     $name = 

        try {
            $update_user_by_id = "UPDATE `users` SET `name` = :name, `email` = :email WHERE `id`=:id";
            $query_stmt = $this->db->prepare($update_user_by_id);
            $query_stmt->bindValue(':name', $name, PDO::PARAM_STR);
            $query_stmt->bindValue(':email', $email, PDO::PARAM_STR);
            $query_stmt->bindValue(':id', $id, PDO::PARAM_INT);
            $query_stmt->execute();

            if ($query_stmt->rowCount()) :
                return true;
            else :
                return false;
            endif;
        } catch (PDOException $e) {
            return null;
        }
    }
}