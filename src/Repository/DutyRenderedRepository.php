<?php

namespace App\Repository;

use App\Document\DutyRendered;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\Repository\DocumentRepository;

/**
 * @extends DocumentRepository<DutyRendered>
 *
 * @method DutyRendered|null find($id, $lockMode = null, $lockVersion = null)
 * @method DutyRendered|null findOneBy(array $criteria, array $orderBy = null)
 * @method DutyRendered[]    findAll()
 * @method DutyRendered[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class DutyRenderedRepository extends DocumentRepository
{
    public function __construct(DocumentManager $dm)
    {
        $uow = $dm->getUnitOfWork();
        $classMetaData = $dm->getClassMetadata(DutyRendered::class);
        parent::__construct($dm, $uow, $classMetaData);
    }

    //    /**
    //     * @return DutyRendered[] Returns an array of DutyRendered objects
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
