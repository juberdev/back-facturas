<?php

namespace App\Core;

use App\Models\Setting;
use App\Models\Sucursal;

class Nubefact {

    private static function obtenerRuta(){
        return env('NUBEFACT_URL');
    }
    private static function obtenerToken(){

        $res = Setting::select('token_nubefact')->first();
        if($res){
            return $res->token_nubefact;
        }
        return null;
    }

    /*
    @@@@@@@@ GENERAR @@@@@@@@
    */
    public static function generar($cabecera,$detalle) {

        date_default_timezone_set('America/Lima');

        //VALIDAR CABECERA
        $cabecera = self::validarCabecera($cabecera);
        if (array_key_exists('errors',$cabecera)) {
            //Retornamos el error
            return $cabecera;
        }

        //VALIDAR DETALLE
        if(Count($detalle)<=0){
            return self::respuesta(null,'Detalle - No hay item de productos');
        }
        
        //GENERAR DETALLE
        $cabecera['items'] = self::generarDetalle($cabecera,$detalle);

        //ENVIAR JSON
        $leer_respuesta = self::enviarJSON($cabecera);
        if (isset($leer_respuesta['errors'])) {
            //Mostramos los errores si los hay
            return self::respuesta(null,$leer_respuesta['errors']);
        } else {
            return self::respuesta($leer_respuesta);
        }
        

    }

    private static function enviarJSON($cabecera){

        $data = array(
            "operacion"				            => "generar_comprobante",
            "tipo_de_comprobante"               => $cabecera['tipo_de_comprobante'], //1 = FACTURA | 2 = BOLETA | 3 = NOTA DE CREDITO | 4 = NOTA DE DEBITO
            "serie"                             => $cabecera['serie'], //"FFF1",
            "numero"				            => $cabecera['numero'],
            "sunat_transaction"			        => $cabecera['sunat_transaction'],
            "cliente_tipo_de_documento"		    => $cabecera['cliente_tipo_de_documento'],
            "cliente_numero_de_documento"	    => $cabecera['cliente_numero_de_documento'],
            "cliente_denominacion"              => utf8_encode($cabecera['cliente_denominacion']),
            "cliente_direccion"                 => utf8_encode($cabecera['cliente_direccion']),
            "cliente_email"                     => $cabecera['cliente_email'],
            "cliente_email_1"                   => "",
            "cliente_email_2"                   => "",
            "fecha_de_emision"                  => $cabecera['fecha_de_emision'],
            "fecha_de_vencimiento"              => "",
            "moneda"                            => $cabecera['moneda'],
            "cancelado"						    => $cabecera['cancelado'], //Pasa a pagado automaticamente
            "tipo_de_cambio"                    => "",
            "porcentaje_de_igv"                 => number_format($cabecera['porcentaje_de_igv'], 2, '.', ''),//"18.00",
            "descuento_global"                  => "",
            "descuento_global"                  => "",
            "total_descuento"                   => "",
            "total_anticipo"                    => "",
            "total_gravada"                     => number_format($cabecera['total_gravada'], 2, '.', ''),
            "total_inafecta"                    => number_format($cabecera['total_inafecta'], 2, '.', ''),
            "total_exonerada"                   => number_format($cabecera['total_exonerada'], 2, '.', ''),
            "total_igv"                         => number_format($cabecera['total_igv'], 2, '.', ''),
            "total_gratuita"                    => "",
            "total_otros_cargos"                => "",
            "total"                             => number_format($cabecera['total_gravada'] + $cabecera['total_inafecta'] + $cabecera['total_exonerada'] + $cabecera['total_igv'], 2, '.', ''),
            "percepcion_tipo"                   => "",
            "percepcion_base_imponible"         => "",
            "total_percepcion"                  => "",
            "total_incluido_percepcion"         => "",
            "detraccion"                        => "false",
            "observaciones"                     => $cabecera['observaciones'],
            "documento_que_se_modifica_tipo"    => "",
            "documento_que_se_modifica_serie"   => "",
            "documento_que_se_modifica_numero"  => "",
            "tipo_de_nota_de_credito"           => "",
            "tipo_de_nota_de_debito"            => "",
            "enviar_automaticamente_a_la_sunat" => "true",
            "enviar_automaticamente_al_cliente" => "false",
            "codigo_unico"                      => "",
            "condiciones_de_pago"               => "",
            "medio_de_pago"                     => $cabecera['medio_de_pago'],
            "placa_vehiculo"                    => "",
            "orden_compra_servicio"             => "",
            "tabla_personalizada_codigo"        => "",
            "formato_de_pdf"                    => $cabecera['formato_de_pdf'],
            "items" 							=> $cabecera['items'],
            "venta_al_credito"					=> $cabecera['venta_al_credito']
        );

        $data_json = json_encode($data);

        //Invocamos el servicio de NUBEFACT
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, self::obtenerRuta());
        curl_setopt(
            $ch, CURLOPT_HTTPHEADER, array(
            'Authorization: Token token="'.self::obtenerToken().'"',
            'Content-Type: application/json',
            )
        );
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_POSTFIELDS,$data_json);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $respuesta  = curl_exec($ch);
        curl_close($ch);

        $leer_respuesta = json_decode($respuesta, true);
        return $leer_respuesta;
    }

    private static function generarDetalle(&$cabecera,$detalle){

        $valor_igv = $cabecera['porcentaje_de_igv']/100;
        $total_gravada = 0;
        $total_inafecta = 0;
        $total_exonerada = 0;
        $total_igv = 0;
        $tmp = array();
        
        foreach ($detalle as $key => $item)
        {
            $db_cantidad = $item['cantidad'];
            $db_valorUnitario = $item['valor_unitario'];
            $db_precioUnitario = 0;
            $db_descuento = 0;
            $db_subTotal = 0;
            $db_igv = 0;
            $db_total = 0;

            //Validar tipo_de_igv
            if (array_key_exists('tipo_de_igv',$item)) {

                if (!is_numeric($item['tipo_de_igv'])){

                    switch (
                        strtoupper($item['tipo_de_igv'])
                    ){
                        case 'GRAVADO': 
                            $item['tipo_de_igv'] = 1;
                            break;
                        case 'INAFECTO':  
                            $item['tipo_de_igv'] = 9;
                            break;
                        case 'EXONERADO':  
                            $item['tipo_de_igv'] = 8;
                            break;
                    }

                }
                
            }else{
                $item['tipo_de_igv'] = 1;
                /*
                1 = Gravado - Operación Onerosa
                2 = Gravado – Retiro por premio
                3 = Gravado – Retiro por donación
                4 = Gravado – Retiro
                5 = Gravado – Retiro por publicidad
                6 = Gravado – Bonificaciones
                7 = Gravado – Retiro por entrega a trabajadores
                8 = Exonerado - Operación Onerosa
                9 = Inafecto - Operación Onerosa
                10 = Inafecto – Retiro por Bonificación
                11 = Inafecto – Retiro
                12 = Inafecto – Retiro por Muestras Médicas
                13 = Inafecto - Retiro por Convenio Colectivo
                14 = Inafecto – Retiro por premio
                15 = Inafecto - Retiro por publicidad
                16 = Exportación
                17 = Exonerado - Transferencia Gratuita
                */
            }
            //Validar tipo_de_igv
            switch (
                intval($item['tipo_de_igv'])
            ){
                //GRAVADO
                case 1:
                case 2:
                case 3:
                case 4:
                case 5:
                case 6:
                case 7:
                    $db_valorUnitario = $db_valorUnitario / (1+$valor_igv);
                    $db_precioUnitario = $db_valorUnitario * (1+$valor_igv);
                    $db_subTotal = $db_valorUnitario * $db_cantidad;
                    $db_igv = $db_subTotal * $valor_igv;
                    $total_gravada = $total_gravada + $db_subTotal; //Sumatoria Gravada de todos los productos
                break;
                
                //INAFECTO
                case 9:
                case 10:
                case 11:
                case 12:
                case 13:
                case 14:
                case 15:
                    $db_precioUnitario = $db_valorUnitario;
                    $db_subTotal = $db_valorUnitario * $db_cantidad;
                    $total_inafecta = $total_inafecta + $db_subTotal; //Sumatoria Inafecto de todos los productos
                break;
                
                //EXONERADO
                case 8:
                case 17:
                    $db_precioUnitario = $db_valorUnitario;
                    $db_subTotal = $db_valorUnitario * $db_cantidad;
                    $total_exonerada = $total_exonerada + $db_subTotal; //Sumatoria Exonerada de todos los productos
                break;
            
            }

            //Validar Codigo
            if (!array_key_exists('codigo',$item)){
                $item['codigo']="";
            }

            //Validar descripcion
            if (!array_key_exists('descripcion',$item)){
                $item['descripcion']="Producto Varios";
            }
            
            $db_total = $db_subTotal + $db_igv;            
            $total_igv = $total_igv + $db_igv; //Sumatoria IGV de todos los productos
            
            $tmp[] = array(
                    "unidad_de_medida" => "NIU",
                    "codigo" => $item['codigo'],
                    "descripcion" => utf8_encode($item['descripcion']),
                    "cantidad" => number_format($db_cantidad, 0, '.', ''),
                    "valor_unitario" => number_format($db_valorUnitario, 2, '.', ''),
                    "precio_unitario" => number_format($db_precioUnitario, 2, '.', ''),
                    "descuento" => "",
                    "subtotal" => number_format($db_subTotal, 2, '.', ''),
                    "tipo_de_igv" => $item['tipo_de_igv'],
                    "igv" => number_format($db_igv, 2, '.', ''),
                    "total" => number_format($db_total, 2, '.', ''),
                    "anticipo_regularizacion" => "false",
                    "anticipo_documento_serie" => "",
                    "anticipo_documento_numero" => ""
            ); 
        }

        /*Actualizamos el Gavada, Inafecta, Exonerada, IGV*/
        $cabecera['total_gravada'] = $total_gravada;
        $cabecera['total_inafecta'] = $total_inafecta;
        $cabecera['total_exonerada'] = $total_exonerada;
        $cabecera['total_igv'] = $total_igv;

        return $tmp;

    }

    private static function validarCabecera($cabecera){

        
        //tipo_de_comprobante
        $serie_temp = '';
        if(array_key_exists('tipo_de_comprobante',$cabecera)){

            switch(
                strtoupper($cabecera['tipo_de_comprobante'])
            ){
                case 'FACTURA':
                    $cabecera['tipo_de_comprobante'] = 1; 
                    $serie_temp = 'FFF';
                    break;

                case 'BOLETA':
                    $cabecera['tipo_de_comprobante'] = 2; 
                    $serie_temp = 'BBB';
                    break;

                default:
                    return self::respuesta(null,'Cabecera - No existe comprobante '.$cabecera['tipo_de_comprobante']);
            }
            
        }else{
            return self::respuesta(null,'Cabecera - Falta la columna: tipo_de_comprobante');
        }

        //serie
        if(array_key_exists('serie',$cabecera)){
            if(self::isEmpty($cabecera['serie'])){
                return self::respuesta(null,'Cabecera - Falta la columna: serie');
            }
            //else{ $cabecera['serie'] = $serie_temp.$cabecera['serie']; }
        }else{
            return self::respuesta(null,'Cabecera - Falta la columna: serie');
        }

        //numero
        if(!array_key_exists('numero',$cabecera)){
            return self::respuesta(null,'Cabecera - Falta la columna: numero');
        }

        //sunat_transaction
        if(!array_key_exists('sunat_transaction',$cabecera)){
            $cabecera['sunat_transaction'] = "1";
            /*
                1 = VENTA INTERNA
                2 = EXPORTACIÓN
                4 = VENTA INTERNA – ANTICIPOS
                29 = VENTAS NO DOMICILIADOS QUE NO CALIFICAN COMO EXPORTACIÓN.
                30 = OPERACIÓN SUJETA A DETRACCIÓN.
                33 = DETRACCIÓN - SERVICIOS DE TRANSPORTE CARGA
                34 = OPERACIÓN SUJETA A PERCEPCIÓN
                32 = DETRACCIÓN - SERVICIOS DE TRANSPORTE DE PASAJEROS.
                31 = DETRACCIÓN - RECURSOS HIDROBIOLÓGICOS
            */
        }

        //cliente_numero_de_documento
        if(!array_key_exists('cliente_numero_de_documento',$cabecera)){
            return self::respuesta(null,'Cabecera - Falta la columna: cliente_numero_de_documento');
        }

        //cliente_tipo_de_documento
        if(array_key_exists('cliente_tipo_de_documento',$cabecera)){

            /*
                6 = RUC - REGISTRO ÚNICO DE CONTRIBUYENTE
                1 = DNI - DOC. NACIONAL DE IDENTIDAD
                - = VARIOS - VENTAS MENORES A S/.700.00 Y OTROS
                4 = CARNET DE EXTRANJERÍA
                7 = PASAPORTE
                A = CÉDULA DIPLOMÁTICA DE IDENTIDAD
                0 = NO DOMICILIADO, SIN RUC (EXPORTACIÓN)
            */

            switch(
                strtoupper($cabecera['cliente_tipo_de_documento'])
            ){
                case 'RUC':
                    $cabecera['cliente_tipo_de_documento'] = 6; 
                    
                    //validar longitud segun tipo
                    IF(strlen(trim($cabecera['cliente_numero_de_documento'])) != 11){
                        return self::respuesta(null,'Cabecera - cliente_numero_de_documento debe tener 11 digitos RUC'.$cabecera['cliente_numero_de_documento']);
                    }

                    break;

                case 'DNI':
                    $cabecera['cliente_tipo_de_documento'] = 1; 

                    //validar longitud segun tipo
                    IF(strlen(trim($cabecera['cliente_numero_de_documento'])) != 8){
                        return self::respuesta(null,'Cabecera - cliente_numero_de_documento debe tener 8 digitos DNI'.$cabecera['cliente_numero_de_documento']);
                    }

                    break;

                default:
                    return self::respuesta(null,'Cabecera - No existe cliente_tipo_de_documento '.$cabecera['cliente_tipo_de_documento']);
            }

        }else{
            $cabecera['cliente_tipo_de_documento'] = "-";
            //$cabecera['cliente_numero_de_documento'] = "";
            //$cabecera['cliente_denominacion'] = "";
            //$cabecera['cliente_direccion'] = "";
            //return self::respuesta(null,'Cabecera - Falta la columna: cliente_tipo_de_documento');
        }

        //cliente_denominacion
        if(!array_key_exists('cliente_denominacion',$cabecera)){
            return self::respuesta(null,'Cabecera - Falta la columna: cliente_denominacion');
        }

        //cliente_direccion
        if(!array_key_exists('cliente_direccion',$cabecera)){
            return self::respuesta(null,'Cabecera - Falta la columna: cliente_direccion');
        }
        
        //cliente_email
        if(array_key_exists('cliente_email',$cabecera)){
            if(self::isEmpty($cabecera['cliente_email'])){
                $cabecera['cliente_email'] = "";
            }
        }else{
            $cabecera['cliente_email'] = "";
        }

        //fecha_de_emision
        if(array_key_exists('fecha_de_emision',$cabecera)){
            if(self::isEmpty($cabecera['fecha_de_emision'])){
                $cabecera['fecha_de_emision'] = date('d-m-Y');
            }
        }else{
            $cabecera['fecha_de_emision'] = date('d-m-Y');
        }

        //moneda
        if(array_key_exists('moneda',$cabecera)){

            /*
                1 = SOLES
                2 = DOLARES
                3 = EUROS
            */

            switch(
                strtoupper($cabecera['moneda'])
            ){
                case 'SOLES':
                    $cabecera['moneda'] = 1; 
                    break;

                case 'DOLARES':
                    $cabecera['moneda'] = 2; 
                    break;
                default:
                    return self::respuesta(null,'Cabecera - No existe moneda '.$cabecera['moneda']);
            }

        }else{
            return self::respuesta(null,'Cabecera - Falta la columna: moneda');
        }

        //porcentaje_de_igv
        if(array_key_exists('porcentaje_de_igv',$cabecera)){
            if(self::isEmpty($cabecera['porcentaje_de_igv'])){
                $cabecera['porcentaje_de_igv'] = 18;
            }
        }else{
            $cabecera['porcentaje_de_igv'] = 18;
        }

        //cancelado
        if(!array_key_exists('cancelado',$cabecera)){
            $cabecera['cancelado']="1";
        }

        //venta_al_credito
        if(!array_key_exists('venta_al_credito',$cabecera)){
            $cabecera['venta_al_credito']=array();
        }else if(Count($cabecera['venta_al_credito']) > 0){
            $cabecera['medio_de_pago'] = "CREDITO";
            $cabecera['cancelado'] = "0";
        }

        //medio_de_pago
        if(!array_key_exists('medio_de_pago',$cabecera)){
            $cabecera['medio_de_pago']="";
        }

        //observaciones
        if(!array_key_exists('observaciones',$cabecera)){
            $cabecera['observaciones']="";
        }

        //formato_de_pdf
        if(array_key_exists('formato_de_pdf',$cabecera)){

            $cabecera['formato_de_pdf'] = strtoupper($cabecera['formato_de_pdf']);

            switch ($cabecera['formato_de_pdf']) {
                case 'A4':
                case 'A5':
                case 'TICKET':
                    $cabecera['formato_de_pdf'] = $cabecera['formato_de_pdf'];
                    break;
                
                default:
                    return self::respuesta(null,'Cabecera - No existe el formato_de_pdf: '.$cabecera['formato_de_pdf']);
                    break;
            }

        }else{
            $cabecera['formato_de_pdf']="A4";
        }

        //idSucursal
        if(!array_key_exists('idSucursal',$cabecera)){
            return self::respuesta(null,'Cabecera - Falta la columna: idSucursal');
        }

        return $cabecera;
    }

    private static function respuesta($respuesta,$error=null){

        $data = array();
        if(!self::isEmpty($respuesta)){
            $data=$respuesta;
        }
        if(!self::isEmpty($error)){
            $data['errors']=$error;
        }
        return $data;
    }

    /*
    @@@@@@@@ ANULAR @@@@@@@@
    */
    public static function anular($cabecera) {

        date_default_timezone_set('America/Lima');

        //VALIDAR CABECERA
        $cabecera = self::anular_validarCabecera($cabecera);
        if (array_key_exists('errors',$cabecera)) {
            //Retornamos el error
            return $cabecera;
        }


        //ENVIAR JSON
        $leer_respuesta = self::anular_enviarJSON($cabecera);
        if (isset($leer_respuesta['errors'])) {
            //Mostramos los errores si los hay
            return self::respuesta(null,$leer_respuesta['errors']);
        } else {
            return self::respuesta($leer_respuesta);
        }
        

    }

    public static function anular_validarCabecera($cabecera){

        //tipo_de_comprobante
        $serie_temp = '';
        if(array_key_exists('tipo_de_comprobante',$cabecera)){

            switch(
                strtoupper($cabecera['tipo_de_comprobante'])
            ){
                case 'FACTURA':
                    $cabecera['tipo_de_comprobante'] = 1; 
                    //$serie_temp = 'FFF';
                    $serie_temp = '';
                    break;

                case 'BOLETA':
                    $cabecera['tipo_de_comprobante'] = 2; 
                    //$serie_temp = 'BBB';
                    $serie_temp = '';
                    break;

                default:
                    return self::respuesta(null,'Cabecera - No existe comprobante '.$cabecera['tipo_de_comprobante']);
            }
            
        }else{
            return self::respuesta(null,'Cabecera - Falta la columna: tipo_de_comprobante');
        }

        //serie
        if(array_key_exists('serie',$cabecera)){
            if(self::isEmpty($cabecera['serie'])){
                return self::respuesta(null,'Cabecera - Falta la columna: serie');
            }else{
                $cabecera['serie'] = $serie_temp.$cabecera['serie'];
            }
        }else{
            return self::respuesta(null,'Cabecera - Falta la columna: serie');
        }

        //numero
        if(!array_key_exists('numero',$cabecera)){
            return self::respuesta(null,'Cabecera - Falta la columna: numero');
        }

        //motivo
        if(!array_key_exists('motivo',$cabecera)){
            return self::respuesta(null,'Cabecera - Falta la columna: motivo');
        }else{
            if(self::isEmpty($cabecera['motivo'])){
                $cabecera['motivo'] = "ERROR DEL SISTEMA";
            }
        }

        //codigo_unico
        if(!array_key_exists('codigo_unico',$cabecera)){
            $cabecera['codigo_unico'] = "";
        }

        return $cabecera;

    }

    private static function anular_enviarJSON($cabecera){

        $data = array(
            "operacion"             => "generar_anulacion",
            "tipo_de_comprobante"   => $cabecera['tipo_de_comprobante'], //1 = FACTURA | 2 = BOLETA | 3 = NOTA DE CREDITO | 4 = NOTA DE DEBITO
            "serie"                 => $cabecera['serie'], //"FFF1",
            "numero"				=> $cabecera['numero'],
            "motivo"			    => $cabecera['motivo'],
            "codigo_unico"		    => $cabecera['codigo_unico'],

        );

        $data_json = json_encode($data);

        //Invocamos el servicio de NUBEFACT
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, self::obtenerRuta());
        curl_setopt(
            $ch, CURLOPT_HTTPHEADER, array(
            'Authorization: Token token="'.self::obtenerToken().'"',
            'Content-Type: application/json',
            )
        );
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_POSTFIELDS,$data_json);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $respuesta  = curl_exec($ch);
        curl_close($ch);

        $leer_respuesta = json_decode($respuesta, true);
        return $leer_respuesta;
    }

    private static function isEmpty($object) {
        if (!isset($object))
            return true;
        if (is_null($object))
            return true;
        if (is_string($object) && strlen($object) <= 0)
            return true;
        if (is_array($object) && empty($object))
            return true;
        if (is_numeric($object) && is_nan($object))
            return true;

        return false;
    }


    private static function consultar_comprobante($cabecera){

        $data = array(
            "operacion"             => "consultar_comprobante",
            "tipo_de_comprobante"   => $cabecera['tipo_de_comprobante'], //1 = FACTURA | 2 = BOLETA | 3 = NOTA DE CREDITO | 4 = NOTA DE DEBITO
            "serie"                 => $cabecera['serie'], //"FFF1",
            "numero"				=> $cabecera['numero']

        );

        $data_json = json_encode($data);

        //Invocamos el servicio de NUBEFACT
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, self::obtenerRuta());
        curl_setopt(
            $ch, CURLOPT_HTTPHEADER, array(
            'Authorization: Token token="'.self::obtenerToken().'"',
            'Content-Type: application/json',
            )
        );
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_POSTFIELDS,$data_json);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $respuesta  = curl_exec($ch);
        curl_close($ch);

        $leer_respuesta = json_decode($respuesta, true);
        return $leer_respuesta;
    }
}