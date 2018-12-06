<?php
namespace VexShipping\Chazki\Api;
 
interface ChazkiInterface
{
    /**
     * Get customer questions
     *
     * @api
     * @param int $customerId
     * @return string questions
     */
    public function gettracking($customerId);
}