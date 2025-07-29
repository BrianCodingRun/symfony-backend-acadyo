<?php

namespace App\Repository;

use App\Document\Submission;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\Repository\DocumentRepository;

/**
 * @extends DocumentRepository<Submission>
 *
 * @method Submission|null find($id, $lockMode = null, $lockVersion = null)
 * @method Submission|null findOneBy(array $criteria, array $orderBy = null)
 * @method Submission[]    findAll()
 * @method Submission[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class SubmissionRepository extends DocumentRepository
{
    public function __construct(DocumentManager $dm)
    {
        $uow = $dm->getUnitOfWork();
        $classMetaData = $dm->getClassMetadata(Submission::class);
        parent::__construct($dm, $uow, $classMetaData);
    }

    //    /**
    //     * @return Submission[] Returns an array of Submission objects
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
