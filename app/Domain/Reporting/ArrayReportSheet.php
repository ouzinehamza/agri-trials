<?php
namespace App\Domain\Reporting;
use Maatwebsite\Excel\Concerns\{FromArray,WithHeadings,WithTitle};
class ArrayReportSheet implements FromArray,WithHeadings,WithTitle { public function __construct(private string $name,private array $headers,private array $rows){} public function array():array{return $this->rows;} public function headings():array{return $this->headers;} public function title():string{return substr($this->name,0,31);} }
