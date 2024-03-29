<?php
namespace Models;

use \Core\Model;
use \Models\Users;

class Photos extends Model
{

    public function getPhotosCount($id)
    {
        $sql = "SELECT COUNT(*) as c FROM photos WHERE id_user = :id";
        $sql = $this->db->prepare($sql);
        $sql->bindValue(':id', $id);
        $sql->execute();
        $info = $sql->fetch();
        return $info['c'];
    }
    public function deleteAll($id_user)
    {
        $sql = "DELETE FROM photos WHERE id_user = :id_user";
        $sql = $this->db->prepare($sql);
        $sql->bindValue(':id_user', $id_user);
        $sql->execute();

        $sql = "DELETE FROM photos_comments WHERE id_user = :id_user";
        $sql = $this->db->prepare($sql);
        $sql->bindValue(':id_user', $id_user);
        $sql->execute();

        $sql = "DELETE FROM photos_likes WHERE id_user = :id_user";
        $sql = $this->db->prepare($sql);
        $sql->bindValue(':id_user', $id_user);
        $sql->execute();
    }

    public function deletePhoto($id_photo, $id_user)
    {
        $sql = "SELECT id FROM photos WHERE id = :id AND id_user = :id_user";
        $sql = $this->db->prepare($sql);
        $sql->bindValue(':id_user', $id_user);
        $sql->bindValue(':id', $id_photo);
        $sql->execute();
        if ($sql->rowCount() > 0) {
            $sql = "DELETE FROM photos WHERE id = :id";
            $sql = $this->db->prepare($sql);
            $sql->bindValue(':id', $id_photo);
            $sql->execute();

            $sql = "DELETE FROM photos_comments WHERE id_photo = :id_photo";
            $sql = $this->db->prepare($sql);
            $sql->bindValue(':id_photo', $id_photo);
            $sql->execute();

            $sql = "DELETE FROM photos_likes WHERE id_photo = :id_photo";
            $sql = $this->db->prepare($sql);
            $sql->bindValue(':id_photo', $id_photo);
            $sql->execute();
            return '';
        } else {
            return 'Essa foto não é sua';
        }
    }
    public function getFeedCollection($ids, $offset, $per_page)
    {
        $array = array();
        $users = new Users();

        if (count($ids) > 0) {
            $sql = "SELECT * FROM photos
             WHERE id_user
              in (" . implode(',', $ids) . ") order by id DESC LIMIT " . $offset . "," . $per_page;
            $sql = $this->db->query($sql);

            if ($sql->rowCount() > 0) {
                $array = $sql->fetchAll(\PDO::FETCH_ASSOC);
                foreach ($array as $k => $item) {
                    $user_info = $users->getInfo($item['id_user']);
                    $array[$k]['name'] = $user_info['name'];
                    $array[$k]['avatar'] = $user_info['avatar'];
                    $array[$k]['url'] = BASE_URL . 'media/photos/' . $item['url'];
                    $array[$k]['like_count'] = $this->getLikeCount($item['id']);
                    $array[$k]['comments'] = $this->getComments($item['id']);

                }

            } else {
                return 'q pena';
            }
        }
        return $array;
    }
    public function getPhoto($id_photo)
    {
        $array = array();
        $users = new Users();

        $sql = "SELECT * FROM photos   WHERE id = :id";
        $sql = $this->db->prepare($sql);
        $sql->bindValue(':id', $id_photo);
        $sql->execute();

        if ($sql->rowCount() > 0) {
            $array = $sql->fetch(\PDO::FETCH_ASSOC);
            $user_info = $users->getInfo($array['id_user']);
            $array['name'] = $user_info['name'];
            $array['avatar'] = $user_info['avatar'];
            $array['url'] = BASE_URL . 'media/photos/' . $array['url'];
            $array['like_count'] = $this->getLikeCount($array['id']);
            $array['comments'] = $this->getComments($array['id']);

        } else {
            return 'q pena';
        }

        return $array;
    }
    public function getLikeCount($id)
    {
        $sql = "SELECT COUNT(*) as c FROM photos_likes WHERE id_photo = :id";
        $sql = $this->db->prepare($sql);
        $sql->bindValue(':id', $id);
        $sql->execute();
        $info = $sql->fetch();
        return $info['c'];
    }
    public function getComments($id_photo)
    {
        $array = array();
        $sql = "SELECT photos_comments.*,users.name FROM photos_comments LEFT JOIN users on users.id = photos_comments.id_user WHERE photos_comments.id_photo = :id";
        $sql = $this->db->prepare($sql);
        $sql->bindValue(':id', $id_photo);
        $sql->execute();
        if ($sql->rowCount() > 0) {
            $array = $sql->fetchAll(\PDO::FETCH_ASSOC);
        }

        return $array;
    }
    public function getPhotosFromUser($id_user, $offset, $per_page)
    {
        $users = new Users();
        $array = array();
        $sql = "SELECT * FROM photos WHERE id_user = :id ORDER BY id DESC LIMIT $offset,$per_page";
        $sql = $this->db->prepare($sql);
        $sql->bindValue(':id', $id_user);
        $sql = $this->db->prepare($sql);
        if ($sql->rowCount() > 0) {
            $array = $sql->fetchAll(\PDO::FETCH_ASSOC);
            foreach ($array as $k => $item) {
                $user_info = $users->getInfo($item['id_user']);
                $array[$k]['name'] = $user_info['name'];
                $array[$k]['avatar'] = $user_info['avatar'];
                $array[$k]['url'] = BASE_URL . 'media/photos/' . $item['url'];
                $array[$k]['like_count'] = $this->getLikeCount($item['id']);
                $array[$k]['comments'] = $this->getComments($item['id']);

            }
        }
        return $array;
    }
    public function getRandomPhotos($per_page, $excludes = array())
    {
        $array = array();

        $sql = "SELECT * FROM photos ORDER BY rand() DESC LIMIT $per_page";

        $sql = $this->db->prepare($sql);
        $sql->execute();
        if ($sql->rowCount() > 0) {
            $array = $sql->fetchAll(\PDO::FETCH_ASSOC);
            foreach ($array as $k => $item) {
                $array[$k]['url'] = BASE_URL . 'media/photos/' . $item['url'];
                $array[$k]['like_count'] = $this->getLikeCount($item['id']);
                $array[$k]['comments'] = $this->getComments($item['id']);
            }
        }

        return $array;

    }
    public function addComment($id_photo, $id_user, $txt)
    {
        if (!empty($txt)) {
            $sql = "INSERT INTO photos_comments (id_user,id_photo,date_comment,txt)
        VALUES (:id_user,:id_photo,NOW(),:txt)";
            $sql = $this->db->prepare($sql);
            $sql->bindValue(':id_user', $id_user);
            $sql->bindValue(':id_photo', $id_photo);
            $sql->bindValue(':txt', $txt);
            $sql->execute();
            return '';
        } else {
            return 'comentario vazio';
        }
    }
    public function deleteComment($id_comment, $id_user)
    {
        $sql = "SELECT id FROM photos_comments WHERE id = :id AND id_user = :id_user";
        $sql = $this->db->prepare($sql);
        $sql->bindValue(':id_user', $id_user);
        $sql->bindValue(':id', $id_comment);
        $sql->execute();
        if ($sql->rowCount() > 0) {

            $sql = "DELETE FROM photos_comments WHERE id = :id";
            $sql = $this->db->prepare($sql);
            $sql->bindValue(':id', $id_comment);
            $sql->execute();

            return '';
        } else {
            return 'Esse comentário não é seu';
        }
    }

    public function like($id_photo, $id_user)
    {
        $sql = "SELECT * FROM photos_likes WHERE id_photo = :id_photo AND id_user = :id_user";
        $sql = $this->db->prepare($sql);
        $sql->bindValue(':id_user', $id_user);
        $sql->bindValue(':id_photo', $id_photo);
        $sql->execute();
        if ($sql->rowCount() == 0) {

            $sql = "INSERT INTO photos_likes (id_user,id_photo) VALUES (:id_user,:id_photo)";
            $sql = $this->db->prepare($sql);
            $sql->bindValue(':id_user', $id_user);
            $sql->bindValue(':id_photo', $id_photo);
            $sql->execute();

            return '';
        } else {
            return 'Você já deu like nesta foto';
        }
    }

    public function unlike($id_photo, $id_user)
    {
        $sql = "DELETE  FROM photos_likes WHERE id_photo = :id_photo AND id_user = :id_user";
        $sql = $this->db->prepare($sql);
        $sql->bindValue(':id_user', $id_user);
        $sql->bindValue(':id_photo', $id_photo);
        $sql->execute();

        return '';
    }

}
