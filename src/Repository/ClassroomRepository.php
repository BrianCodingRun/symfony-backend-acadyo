<?php

namespace App\Repository;

use App\Document\Classroom;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\Repository\DocumentRepository;

/**
 * @extends DocumentRepository<Classroom>
 *
 * @method Classroom|null find($id, $lockMode = null, $lockVersion = null)
 * @method Classroom|null findOneBy(array $criteria, array $orderBy = null)
 * @method Classroom[]    findAll()
 * @method Classroom[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ClassroomRepository extends DocumentRepository
{
    public function __construct(DocumentManager $dm)
    {
        $uow = $dm->getUnitOfWork();
        $classMetaData = $dm->getClassMetadata(Classroom::class);
        parent::__construct($dm, $uow, $classMetaData);
    }

    //    /**
    //     * @return Classroom[] Returns an array of Classroom objects
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
