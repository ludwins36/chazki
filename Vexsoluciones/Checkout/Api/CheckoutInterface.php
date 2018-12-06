<?php
namespace Vexsoluciones\Checkout\Api;
 
interface CheckoutInterface
{
    /**
     * Returns greeting message to user
     *
     * @api
     * @param string $name Users name.
     * @return string Greeting message with users name.
     */
    public function listardepartamentos();
    /**
     * Returns greeting message to user
     *
     * @api
     * @param string $departamento Users name.
     * @return string Greeting message with users name.
     */
    public function listarprovincias($departamento);
    /**
     * Returns greeting message to user
     *
     * @api
     * @param string $provincia Users name.
     * @return string Greeting message with users name.
     */
    public function listardistritos($provincia);
    /**
     * Returns greeting message to user
     *
     * @api
     * @param string $provincia Users name.
     * @return string Greeting message with users name.
     */
    public function listartiendas();
    /**
     * Returns greeting message to user
     *
     * @api
     * @param string $provincia Users name.
     * @return string Greeting message with users name.
     */
    public function listarpaises();
    
}