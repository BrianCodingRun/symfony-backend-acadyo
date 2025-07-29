<?php

namespace App\Repository;

use App\Document\Lesson;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\Repository\DocumentRepository;

/**
 * @extends DocumentRepository<Lesson>
 *
 * @method Lesson|null find($id, $lockMode = null, $lockVersion = null)
 * @method Lesson|null findOneBy(array $criteria, array $orderBy = null)
 * @method Lesson[]    findAll()
 * @method Lesson[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class LessonRepository extends DocumentRepository
{
    public function __construct(DocumentManager $dm)
    {
        $uow = $dm->getUnitOfWork();
        $classMetaData = $dm->getClassMetadata(Lesson::class);
        parent::__construct($dm, $uow, $classMetaData);
    }

//    /**
//     * @return Lesson[] Returns an array of Lesson objects
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
