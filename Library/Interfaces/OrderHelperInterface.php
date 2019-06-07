<?php
namespace SantanderPaymentSolutions\SantanderPayments\Library\Interfaces;

interface OrderHelperInterface{

    /**
     * @param string $reference
     *
     * @return bool
     */
    public function isAllowedToFinalize($reference);

    /**
     * @param string $reference
     *
     * @return bool
     */
    public function isCompletelyReversed($reference);

    /**
     * @param string $reference
     *
     * @return float
     */
    public function getOpenAmount($reference);

    /**
     * @param string $reference
     *
     * @return bool
     */
    public function isCompleteSuccess($reference);

    /**
     * @param $reference
     *
     * @return bool
     */
    public function isFailure($reference);

}