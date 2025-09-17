<?php

namespace Noki\XmlConverter;

use SimpleXMLElement;
use DOMDocument;
use InvalidArgumentException;
use Illuminate\Support\Facades\Log;

class Convert
{
    /**
     * Check are required extensions installed.
     *
     * @throws \RuntimeException
     * @return void
     */
    protected static function checkExtensions(): void
    {
        if (!extension_loaded('simplexml')) {
            throw new \RuntimeException("Required PHP extensions not installed: simplexml.");
        }

        if (!extension_loaded('libxml')) {
            throw new \RuntimeException("Required PHP extensions not installed: libxml.");
        }

        if (!extension_loaded('dom')) {
            throw new \RuntimeException("Required PHP extension not installed: dom.");
        }
    }

    /**
     * Convert XML string to JSON string.
     *
     * @param string $xml_string
     * @param bool $namespace_in_tag_name
     * @param bool $is_cdata
     * @param string|null $schema_path
     * @return string
     */
    public static function xmlToJson(
        string $xml_string,
        bool $namespace_in_tag_name = false,
        bool $is_cdata = false,
        string|null $schema_path = null
    ): string
    {
        $array = self::xmlToArray($xml_string, $namespace_in_tag_name, $is_cdata, $schema_path);

        // Convert to JSON
        return json_encode($array, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }

    /**
     * Convert XML string to array.
     *
     * @param string $xml_string
     * @param bool $namespace_in_tag_name
     * @param bool $is_cdata
     * @param string|null $schema_path
     * @return array
     */
    public static function xmlToArray(
        string $xml_string,
        bool $namespace_in_tag_name = false,
        bool $is_cdata = false,
        string|null $schema_path = null
    ): array
    {
        self::checkExtensions();

        self::validateSchema($schema_path, $xml_string);

        // Handle errors
        libxml_use_internal_errors(true);

        // Load the XML, keeping CDATA intact
        $simple_xml = simplexml_load_string($xml_string, "SimpleXMLElement", $is_cdata ? 0 : LIBXML_NOCDATA);

        if ($simple_xml === false) {
            $error = libxml_get_last_error();
            libxml_clear_errors();
            throw new InvalidArgumentException('Invalid XML: ' . ($error ? $error->message : 'Unknown error'));
        }

        // Convert to array (preserving namespaces)
        $result = self::xmlToArrayWithNamespaceTagging($simple_xml, $namespace_in_tag_name, $is_cdata);
        return !empty($result) ? $result : [];
    }

    /**
     * @param string|null $schema_path
     * @param string $xml_string
     * @return void
     */
    protected static function validateSchema(string|null $schema_path, string $xml_string): void
    {
        if ($schema_path === '') {
            libxml_use_internal_errors(true);

            // DTD validation
            $dom = new DOMDocument();
            // Enable DTD loading and validation
            $dom->validateOnParse = true;

            // Load the XML (must contain <!DOCTYPE ...> with DTD reference)
            if (!$dom->loadXML($xml_string, LIBXML_DTDVALID)) {
                $errors = libxml_get_errors();
                libxml_clear_errors();

                foreach ($errors as $error) {
                    $message = "DTD Load Error: " . trim($error->message);
                    Log::error($message);
                }
            }

            if (!$dom->validate()) {
                throw new InvalidArgumentException('XML failed DTD validation.');
            }

        } elseif (!is_null($schema_path)) {
            libxml_use_internal_errors(true);
            libxml_get_errors();

            // XSD validation
            $dom = new DOMDocument();
            $xml_shema_loaded = $dom->loadXML($xml_string);

            $errors = libxml_get_errors();

            foreach ($errors as $error) {
                Log::error("XSD Validation Error", ['message' => $error->message]);
            }
            libxml_clear_errors();

            if (!$xml_shema_loaded) {
                throw new InvalidArgumentException("Invalid XML for schema validation.");
            }

            if (!@$dom->schemaValidate($schema_path)) {
                throw new InvalidArgumentException('XML failed XSD validation.');
            }

        }

    }

    /**
     * Convert SimpleXMLElement to array while tagging namespaces.
     * Return type should be array but since nested values from recursive calls return type can be different.
     *
     * @param SimpleXMLElement $xml
     * @param bool $namespace_in_tag_name
     * @param bool $is_cdata
     * @return mixed
     */
    protected static function xmlToArrayWithNamespaceTagging(
        SimpleXMLElement $xml,
        bool $namespace_in_tag_name,
        bool $is_cdata
    ): mixed
    {
        $result = [];

        // If the XML has namespaces, iterate over them; otherwise, just iterate over the children
        $namespaces = $xml->getNamespaces(true);

        // If there are namespaces, process them
        if (!empty($namespaces)) {
            foreach ($namespaces as $prefix => $ns_uri) {
                foreach ($xml->children($ns_uri) as $child_name => $child) {
                    $clean_name = $child->getName(); // Strips namespace prefix
                    $child_prefix = $prefix;

                    try {
                        $entry = self::xmlToArrayWithNamespaceTagging($child, $namespace_in_tag_name, $is_cdata);
                    } catch (\Exception $e) {
                        Log::error('Skipping element due to error', [
                            'element' => $child_name,
                            'exception' => $e->getMessage(),
                        ]);
                        continue; // Skip this element
                    }

                    if ($namespace_in_tag_name && $child_prefix) {
                        $namespace_key = "{$child_prefix}:{$clean_name}";
                    } else {
                        $namespace_key = $clean_name;
                    }

                    if (!isset($result[$namespace_key])) {
                        $result[$namespace_key] = [];
                    }

                    if ($namespace_in_tag_name && is_array($entry)) {
                        $entry["@namespace"] = $child_prefix;
                    }

                    $result[$namespace_key] = $entry;
                }

            }
        } else {
            // If there are no namespaces, just iterate over the children directly
            foreach ($xml->children() as $child_name => $child) {
                $clean_name = $child->getName(); // Strips namespace prefix

                try {
                    $entry = self::xmlToArrayWithNamespaceTagging($child, $namespace_in_tag_name, $is_cdata);
                } catch (\Exception $e) {
                    Log::error('Skipping element due to error', [
                        'element' => $child_name,
                        'exception' => $e->getMessage(),
                    ]);
                    continue; // Skip this element
                }

                $namespace_key = $clean_name;

                if (!isset($result[$namespace_key])) {
                    $result[$namespace_key] = [];
                }

                $result[$namespace_key] = $entry;
            }
        }

        // Merge attributes with child elements (if any) instead of storing them separately
        foreach ($xml->attributes() as $attr_name => $attr_val) {
            $result["@attributes"][$attr_name] = (string)$attr_val;
        }

        // If no children and it's a simple value (CDATA or plain text)
        $text_content = trim((string)$xml);
        if (empty($result) && !$is_cdata) {
            // Return the plain text without wrapping in an array
            return self::autoCastValue($text_content, false);
        }

        // If there's a value and also other elements/attributes
        if ($text_content !== '') {
//            $result["value"] = $text_content;

            $result["value"] = self::autoCastValue($text_content, $is_cdata);
        }

        return $result;
    }

    /**
     * Convert string values to custom type, skipping conversion for CDATA.
     *
     * @param $value
     * @param bool $is_cdata
     * @return mixed
     */
    protected static function autoCastValue($value, bool $is_cdata):mixed
    {
        if ($is_cdata) {
            return $value;  // Return the raw CDATA content without conversion
        }

        if ( ($value === "")
            || ($value === null)
            || ( is_object($value) && empty($value))
        ) {
            return null;
        }

        if (!is_string($value)) return $value;

        $lower = strtolower($value);

        if ($lower === 'true') return true;
        if ($lower === 'false') return false;
        if (is_numeric($value)) {
            // Don't convert numbers with leading zeros
            if (preg_match('/^0[0-9]+$/', $value)) {
                return $value;
            }

            return strpos($value, '.') !== false ? (float)$value : (int)$value;
        }

        return $value;

    }

}
