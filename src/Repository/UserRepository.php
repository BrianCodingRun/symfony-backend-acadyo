<?php

namespace App\Repository;

use App\Document\User;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\Repository\DocumentRepository;

/**
 * @extends DocumentRepository<User>
 *
 * @method User|null find($id, $lockMode = null, $lockVersion = null)
 * @method User|null findOneBy(array $criteria, array $orderBy = null)
 * @method User[]    findAll()
 * @method User[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class UserRepository extends DocumentRepository
{
    public function __construct(DocumentManager $dm)
    {
        $uow = $dm->getUnitOfWork();
        $classMetaData = $dm->getClassMetadata(User::class);
        parent::__construct($dm, $uow, $classMetaData);
    }

    //    /**
    //     * @return User[] Returns an array of User objects
    //     */
    //    public function findByExampleField($value)
    //    {
    //        return $this->createQueryBuilder()
    //            ->addAnd(['exampleField' => ['$regex' => $value, '$options' => 'i']])
    //            ->sort('exampleField', 'ASC')
    //            ->limit(10)
    //            ->getQuery()
    //            ->execute()
    //        ;
    //    }

    //    public function count(): int
    //    {
    //        $qb = $this->createQueryBuilder();
    //        return $qb->count()->getQuery()->execute();
    //    }
}
