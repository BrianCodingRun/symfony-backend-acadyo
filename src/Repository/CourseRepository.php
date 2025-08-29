<?php

namespace App\Repository;

use App\Document\Course;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\Repository\DocumentRepository;

/**
 * @extends DocumentRepository<Course>
 *
 * @method Course|null find($id, $lockMode = null, $lockVersion = null)
 * @method Course|null findOneBy(array $criteria, array $orderBy = null)
 * @method Course[]    findAll()
 * @method Course[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class CourseRepository extends DocumentRepository
{
    public function __construct(DocumentManager $dm)
    {
        $uow = $dm->getUnitOfWork();
        $classMetaData = $dm->getClassMetadata(Course::class);
        parent::__construct($dm, $uow, $classMetaData);
    }

    //    /**
//     * @return Course[] Returns an array of Course objects
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
