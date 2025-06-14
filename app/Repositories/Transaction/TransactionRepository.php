<?php

namespace App\Repositories\Transaction;

use App\Models\Transaction;
use App\Repositories\Contracts\TransactionRepositoryInterface;
use App\Repositories\BaseRepository;

class TransactionRepository extends BaseRepository implements TransactionRepositoryInterface
{
    protected $entity;

    public function __construct(Transaction $model)
    {
        $this->entity = $model;
    }
}
