<?php
namespace SantanderPaymentSolutions\SantanderPayments\Library\Traits;

trait FrontendUtils{
    protected $method;
    public function getBirthdayInput( \DateTime $date = null ) {
        $method      = $this->method;
        $presetDay   = ( is_object( $date ) ? $date->format( 'd' ) : '' );
        $presetMonth = ( is_object( $date ) ? $date->format( 'm' ) : '' );
        $presetYear  = ( is_object( $date ) ? $date->format( 'Y' ) : '' );
        $html        = '<div class="santander-input-section-head">' . __( 'Ihr Geburtsdatum', 'santander-payment-solutions' ) . '</div>
            <div class="santander-birthday-input-wr" style="margin-bottom:10px;">
                <select name="santander_' . $method . '_birthday[day]">
                    <option disabled="disabled" selected="selected" value="">' . __( 'Tag', 'santander-payment-solutions' ) . '</option>';
        for ( $day = 1; $day <= 31; $day ++ ) {
            $value = str_pad( $day, 2, '0', STR_PAD_LEFT );
            $html  .= '<option value="' . $value . '" ' . ( $presetDay === $value ? 'selected="selected"' : '' ) . '>' . $day . '</option>';
        }
        $html .= '
                </select>.<select name="santander_' . $method . '_birthday[month]">
                    <option disabled="disabled" selected="selected" value="">' . __( 'Monat', 'santander-payment-solutions' ) . '</option>';
        for ( $month = 1; $month <= 12; $month ++ ) {
            $value = str_pad( $month, 2, '0', STR_PAD_LEFT );
            $html  .= '<option value="' . $value . '" ' . ( $presetMonth === $value ? 'selected="selected"' : '' ) . '>' . $month . '</option>';
        }
        $html .= '
                </select>.<select name="santander_' . $method . '_birthday[year]">
                    <option disabled="disabled" selected="selected" value="">' . __( 'Jahr', 'santander-payment-solutions' ) . '</option>';
        for ( $year = date( 'Y' ); $year >= date( 'Y' ) - 120; $year -- ) {
            $year = (string) $year;
            $html .= '<option value="' . $year . '" ' . ( $presetYear === $year ? 'selected="selected"' : '' ) . '>' . $year . '</option>';
        }
        $html .= '
                </select>
            </div>';

        return $html;
    }

    public function getGenderInput() {
        $method = $this->method;

        $html = '<div class="santander-input-section-head">' . __( 'Anrede', 'santander-payment-solutions' ) . '</div>
            <div class="santander-gender-input-wr" style="margin-bottom:10px;">
                <select name="santander_' . $method . '[gender]">
                    <option disabled="disabled" value="" selected="selected"></option>
                    <option value="MRS">Frau</option>
                    <option value="MR">Herr</option>
                </select>
             </div>';

        return $html;
    }

}