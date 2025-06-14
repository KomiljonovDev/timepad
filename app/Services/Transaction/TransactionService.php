<?php

namespace App\Services\Transaction;

use App\Services\Contracts\TransactionServiceInterface;
use App\Repositories\Contracts\TransactionRepositoryInterface;
use App\Services\BaseService;
class TransactionService extends BaseService implements TransactionServiceInterface
{
    public function __construct(TransactionRepositoryInterface $repository)
    {
        parent::__construct($repository);
    }
}
