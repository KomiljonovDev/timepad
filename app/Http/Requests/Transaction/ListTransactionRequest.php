<?php

namespace App\Http\Requests\Transaction;

use Illuminate\Foundation\Http\FormRequest;

class ListTransactionRequest extends FormRequest
{
    public function authorize(): bool {
        return true;
    }

    public function rules(): array {
        return [
            "per_page" => ["nullable", "integer", "min:1", "max:100"],
            "page" => ["nullable", "integer", "min:1"],
            "sort_by" => ["nullable", "string"],
            "order_by" => ["nullable", "in:asc,desc"],
            "filters" => ["nullable", "array"],
            "filters.*.key" => ["required_with:filters", "string"],
            "filters.*.value" => ["required_with:filters", "nullable"],
            "filters.*.type" => ["required_with:filters", "in:string,exact_string,array,not_in_array,intarray,intarrayand,number,bool,day,to,date,datefrom,dateto,from,json,between,isNull,isNotNull,custom"]
        ];
    }
}
