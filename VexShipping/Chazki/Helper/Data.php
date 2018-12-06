<?php
namespace VexShipping\Chazki\Helper;

class Data
{
    
    public function getCiudades($departamento, $provincia, $distrito)
    {
        $datos = array('15'=>
                    array("127"=>
                        array(
                            '1252'=>true,'1265'=>true,'1281'=>true,'1251'=>true,'1253'=>true,
                            '1282'=>true,'1254'=>true,'1266'=>true,'1283'=>true,'1255'=>true,
                            '1267'=>true,'1284'=>true,'1256'=>true,'1270'=>true,'1271'=>true,
                            '1286'=>true,'1259'=>true,'1272'=>true,'1287'=>true,'1260'=>true,
                            '1273'=>true,'1290'=>true,'1261'=>true,'1291'=>true,'1262'=>true,
                            '1275'=>true,'1292'=>true,'1292'=>true,'1263'=>true,'1278'=>true,
                            '1293'=>true,'1264'=>true,'1280'=>true,'1269'=>true,'1285'=>true,
                            '1258'=>true
                        )
                    )
                );

        $aux = (isset($datos[$departamento][$provincia][$distrito]))?true:false;
        return $aux;
    }
    
}
