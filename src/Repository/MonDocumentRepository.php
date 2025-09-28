<?php

namespace App\Repository;

use App\Document\MonDocument;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\Repository\DocumentRepository;

/**
 * @extends DocumentRepository<MonDocument>
 *
 * @method MonDocument|null find($id, $lockMode = null, $lockVersion = null)
 * @method MonDocument|null findOneBy(array $criteria, array $orderBy = null)
 * @method MonDocument[]    findAll()
 * @method MonDocument[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class MonDocumentRepository extends DocumentRepository
{
    public function __construct(DocumentManager $dm)
    {
        $uow = $dm->getUnitOfWork();
        $classMetaData = $dm->getClassMetadata(MonDocument::class);
        parent::__construct($dm, $uow, $classMetaData);
    }

    //    /**
    //     * @return MonDocument[] Returns an array of MonDocument objects
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
