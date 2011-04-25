<?php
class Protolus{
    public static  protolusVersion = '{$ version $}'; // <- replace as part of the build process
    public static protolusBuild = '{$ build $}'; // <- replace as part of the build process
    public static protolusImplementation = 'php';
    public static protolusApplication = 'Protolus';
    public static protolusIdentity = '';
}
Protolus.protolusIdentity = Protolus.protolusApplication.'.'.Protolus.protolusImplementation.' v.'.Protolus.protolusVersion.'(build '.Protolus.protolusBuild.')';
?>