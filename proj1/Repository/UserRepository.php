<?php

namespace App\Repository;

use App\Entity\User;
use Doctrine\ORM\EntityRepository;

class UserRepository extends EntityRepository
{
    /**
     * @return User[]
     */
    public function findAllEnabled()
    {
        return $this->createQueryBuilder('user')
            ->andWhere('user.isEnabled = :isEnabled')
            ->setParameter('isEnabled', true)
            ->orderBy('genus.id', 'DESC')
            ->getQuery()
            ->execute();
    }
    public function countUsers()
    {
        $data = $this->getEntityManager()
            ->createQuery(
                'SELECT COUNT(u.id) FROM App\Entity\User u'
            )
            ->getSingleScalarResult();
        return $data;
    }

    public function getUsersQuery()
    {
        $data = $this->getEntityManager()
            ->createQuery(
                'SELECT u FROM App\Entity\User u ORDER by u.id'
            );
        return $data;

    }
    public function findUserByNameOrEmail($username, $email)
    {
        $data = $this->getEntityManager()
            ->createQuery(
                'SELECT u FROM App\Entity\User u WHERE u.username = :username OR u.email = :email'
            )
            ->setParameters([
                ':username' => $username,
                ':email' => $email
            ])
            ->getResult();

        return $data;

    }
    public function findOthersUserByNameOrEmail($username, $email, $id)
    {
        $data = $this->getEntityManager()
            ->createQuery(
                'SELECT u FROM App\Entity\User u WHERE (u.username = :username OR u.email = :email ) AND u.id <> :id'
            )
            ->setParameters([
                ':username' => $username,
                ':email' => $email,
                ':id' => $id
            ])
            ->getResult();

        return $data;

    }
}
