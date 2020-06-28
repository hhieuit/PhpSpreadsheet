<?php

namespace PhpOffice\PhpSpreadsheet\Cell;

use PhpOffice\PhpSpreadsheet\Exception;

class AddressHelper
{
    /**
     * Converts an R1C1 format cell address to an A1 format cell address.
     */
    public static function convertToA1(
        string $address,
        int $currentRowNumber = 1,
        int $currentColumnNumber = 1
    ): string {
        $validityCheck = preg_match('/^(R(\[?-?\d*\]?))(C(\[?-?\d*\]?))$/i', $address, $cellReference);

        if ($validityCheck === 0) {
            throw new Exception('Invalid R1C1-format Cell Reference');
        }

        $rowReference = $cellReference[2];
        //    Empty R reference is the current row
        if ($rowReference === '') {
            $rowReference = (string) $currentRowNumber;
        }
        //    Bracketed R references are relative to the current row
        if ($rowReference[0] === '[') {
            $rowReference = $currentRowNumber + trim($rowReference, '[]');
        }
        $columnReference = $cellReference[4];
        //    Empty C reference is the current column
        if ($columnReference === '') {
            $columnReference = (string) $currentColumnNumber;
        }
        //    Bracketed C references are relative to the current column
        if ($columnReference[0] === '[') {
            $columnReference = $currentColumnNumber + trim($columnReference, '[]');
        }

        if ($columnReference <= 0 || $rowReference <= 0) {
            throw new Exception('Invalid R1C1-format Cell Reference, Value out of range');
        }
        $A1CellReference = Coordinate::stringFromColumnIndex($columnReference) . $rowReference;

        return $A1CellReference;
    }

    /**
     * Converts a formula that uses R1C1 format cell address to an A1 format cell address.
     */
    public static function convertFormulaToA1(
        string $formula,
        int $currentRowNumber = 1,
        int $currentColumnNumber = 1
    ): string {
echo "R1C1 FORMULA VALUE IS {$formula}", PHP_EOL;
        if (substr($formula, 0, 3) == 'of:') {
            $formula = substr($formula, 3);
            $temp = explode('"', $formula);
            $key = false;
            foreach ($temp as &$value) {
                //    Only replace in alternate array entries (i.e. non-quoted blocks)
                if ($key = !$key) {
                    $value = str_replace(['[.', '.', ']'], '', $value);
                }
            }
        } else {
            //    Convert R1C1 style references to A1 style references (but only when not quoted)
            $temp = explode('"', $formula);
            $key = false;
            foreach ($temp as &$value) {
                //    Only replace in alternate array entries (i.e. non-quoted blocks)
                if ($key = !$key) {
                    preg_match_all('/(R(\[?-?\d*\]?))(C(\[?-?\d*\]?))/', $value, $cellReferences, PREG_SET_ORDER + PREG_OFFSET_CAPTURE);
                    //    Reverse the matches array, otherwise all our offsets will become incorrect if we modify our way
                    //        through the formula from left to right. Reversing means that we work right to left.through
                    //        the formula
                    $cellReferences = array_reverse($cellReferences);
                    //    Loop through each R1C1 style reference in turn, converting it to its A1 style equivalent,
                    //        then modify the formula to use that new reference
                    foreach ($cellReferences as $cellReference) {
                        $A1CellReference = self::convertToA1($cellReference[0][0], $currentRowNumber, $currentColumnNumber);
                        $value = substr_replace($value, $A1CellReference, $cellReference[0][1], strlen($cellReference[0][0]));
                    }
                }
            }
        }
        unset($value);
        //    Then rebuild the formula string
        $formula = implode('"', $temp);

        echo "A1 FORMULA VALUE IS {$formula}", PHP_EOL, PHP_EOL;
        return $formula;
    }

    /**
     * Converts an A1 format cell address to an R1C1 format cell address.
     * If $currentRowNumber or $currentColumnNumber are provided, then the R1C1 address will be formatted as a relative address.
     */
    public static function convertToR1C1(
        string $address,
        ?int $currentRowNumber = null,
        ?int $currentColumnNumber = null
    ): string {
        $validityCheck = preg_match('/^\$?([A-Z]{1,3})\$?(\d{1,7})$/i', $address, $cellReference);

        if ($validityCheck === 0) {
            throw new Exception('Invalid A1-format Cell Reference');
        }

        $columnId = Coordinate::columnIndexFromString($cellReference[1]);
        $rowId = (int) $cellReference[2];

        if ($currentRowNumber !== null) {
            if ($rowId === $currentRowNumber) {
                $rowId = '';
            } else {
                $rowId = '[' . ($rowId - $currentRowNumber) . ']';
            }
        }

        if ($currentColumnNumber !== null) {
            if ($columnId === $currentColumnNumber) {
                $columnId = '';
            } else {
                $columnId = '[' . ($columnId - $currentColumnNumber) . ']';
            }
        }

        $R1C1Address = "R{$rowId}C{$columnId}";

        return $R1C1Address;
    }
}
