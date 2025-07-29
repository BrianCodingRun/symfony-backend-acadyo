<?php

namespace App\Repository;

use App\Document\Assignment;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\Repository\DocumentRepository;

/**
 * @extends DocumentRepository<Assignment>
 *
 * @method Assignment|null find($id, $lockMode = null, $lockVersion = null)
 * @method Assignment|null findOneBy(array $criteria, array $orderBy = null)
 * @method Assignment[]    findAll()
 * @method Assignment[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class AssignmentRepository extends DocumentRepository
{
    public function __construct(DocumentManager $dm)
    {
        $uow = $dm->getUnitOfWork();
        $classMetaData = $dm->getClassMetadata(Assignment::class);
        parent::__construct($dm, $uow, $classMetaData);
    }

//    /**
//     * @return Assignment[] Returns an array of Assignment objects
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
