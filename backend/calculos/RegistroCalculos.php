<?php

final class RegistroCalculos
{
    /**
     * Resuelve una clave almacenada en la plantilla a un callable permitido.
     * Nunca se evalúa código proveniente de la base de datos.
     */
    public static function resolver(string $clave): callable
    {
        $calculadores = [
            'resultado_propuesto' => static function (array $contexto) {
                return $contexto['resultado_propuesto'] ?? null;
            },
        ];

        if (!isset($calculadores[$clave])) {
            throw new RuntimeException("El calculador '{$clave}' no está registrado.");
        }

        return $calculadores[$clave];
    }
}
