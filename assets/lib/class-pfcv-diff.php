<?php
/**
 * Pfcv_Diff
 *
 * A class for comparing text or files and generating differences.
 *
 * This class provides methods to compare strings or files line by line
 * or character by character, and generate differences in various formats
 * such as plain text, HTML, and HTML table.
 *
 * @package PluginFilesComparison
 */

/**
 * Exit if accessed directly.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Displat the differences.
 */
class Pfcv_Diff {

	const UNMODIFIED = 0;
	const DELETED    = 1;
	const INSERTED   = 2;

	/**
	 * Compare two strings or files and return differences.
	 *
	 * @param string $string1 The first string.
	 * @param string $string2 The second string.
	 * @param bool   $compare_characters Whether to compare character by character.
	 * @return array The diff array.
	 */
	public static function compare( $string1, $string2, $compare_characters = false ) {
		$start = 0;
		$sequence1 = $compare_characters ? $string1 : preg_split( '/\n|\r\n?/', $string1 );
		$sequence2 = $compare_characters ? $string2 : preg_split( '/\n|\r\n?/', $string2 );

		$end1 = $compare_characters ? strlen( $string1 ) - 1 : count( $sequence1 ) - 1;
		$end2 = $compare_characters ? strlen( $string2 ) - 1 : count( $sequence2 ) - 1;

		while ( $start <= $end1 && $start <= $end2 && $sequence1[ $start ] == $sequence2[ $start ] ) {
			$start++;
		}

		while ( $end1 >= $start && $end2 >= $start && $sequence1[ $end1 ] == $sequence2[ $end2 ] ) {
			$end1--;
			$end2--;
		}

		$table = self::compute_table( $sequence1, $sequence2, $start, $end1, $end2 );
		$partial_diff = self::generate_partial_diff( $table, $sequence1, $sequence2, $start );

		$diff = array();
		for ( $index = 0; $index < $start; $index++ ) {
			$diff[] = array( $sequence1[ $index ], self::UNMODIFIED );
		}
		while ( ! empty( $partial_diff ) ) {
			$diff[] = array_pop( $partial_diff );
		}

		$sequence1_length = $compare_characters ? strlen( $sequence1 ) : count( $sequence1 );
		for ( $index = $end1 + 1; $index < $sequence1_length; $index++ ) {
			$diff[] = array( $sequence1[ $index ], self::UNMODIFIED );
		}

		return $diff;
	}



	/**
	 * Compare two files and return differences.
	 *
	 * @param string $file1 The path to the first file.
	 * @param string $file2 The path to the second file.
	 * @param bool   $compare_characters Whether to compare character by character.
	 * @return array The diff array.
	 */
	public static function compare_files( $file1, $file2, $compare_characters = false ) {
		return self::compare(
			file_get_contents( $file1 ),
			file_get_contents( $file2 ),
			$compare_characters
		);
	}

	/**
	 * Compute the difference table for two sequences.
	 *
	 * @param array $sequence1 First sequence.
	 * @param array $sequence2 Second sequence.
	 * @param int   $start Start index for the comparison.
	 * @param int   $end1 End index for the first sequence.
	 * @param int   $end2 End index for the second sequence.
	 * @return array The computed table.
	 */
	private static function compute_table( $sequence1, $sequence2, $start, $end1, $end2 ) {
		$length1 = $end1 - $start + 1;
		$length2 = $end2 - $start + 1;

		$table = array_fill( 0, $length1 + 1, array_fill( 0, $length2 + 1, 0 ) );

		for ( $index1 = 1; $index1 <= $length1; $index1++ ) {
			for ( $index2 = 1; $index2 <= $length2; $index2++ ) {
				if ( $sequence1[ $index1 + $start - 1 ] == $sequence2[ $index2 + $start - 1 ] ) {
					$table[ $index1 ][ $index2 ] = $table[ $index1 - 1 ][ $index2 - 1 ] + 1;
				} else {
					$table[ $index1 ][ $index2 ] = max( $table[ $index1 - 1 ][ $index2 ], $table[ $index1 ][ $index2 - 1 ] );
				}
			}
		}

		return $table;
	}

	/**
	 * Generate partial difference from the computed table.
	 *
	 * @param array $table The computed table.
	 * @param array $sequence1 First sequence.
	 * @param array $sequence2 Second sequence.
	 * @param int   $start Start index for the comparison.
	 * @return array The partial diff array.
	 */
	private static function generate_partial_diff( $table, $sequence1, $sequence2, $start ) {
		$diff = array();
		$index1 = count( $table ) - 1;
		$index2 = count( $table[0] ) - 1;

		while ( $index1 > 0 || $index2 > 0 ) {
			if ( $index1 > 0 && $index2 > 0 && $sequence1[ $index1 + $start - 1 ] == $sequence2[ $index2 + $start - 1 ] ) {
				$diff[] = array( $sequence1[ $index1 + $start - 1 ], self::UNMODIFIED );
				$index1--;
				$index2--;
			} elseif ( $index2 > 0 && $table[ $index1 ][ $index2 ] == $table[ $index1 ][ $index2 - 1 ] ) {
				$diff[] = array( $sequence2[ $index2 + $start - 1 ], self::INSERTED );
				$index2--;
			} else {
				$diff[] = array( $sequence1[ $index1 + $start - 1 ], self::DELETED );
				$index1--;
			}
		}

		return $diff;
	}

	/**
	 * Convert the diff array to a string representation.
	 *
	 * @param array  $diff The diff array.
	 * @param string $separator Separator between lines.
	 * @return string The diff string.
	 */
	public static function to_string( $diff, $separator = "\n" ) {
		$string = '';
		foreach ( $diff as $line ) {
			switch ( $line[1] ) {
				case self::UNMODIFIED:
					$string .= '  ' . $line[0];
					break;
				case self::DELETED:
					$string .= '- ' . $line[0];
					break;
				case self::INSERTED:
					$string .= '+ ' . $line[0];
					break;
			}
			$string .= $separator;
		}
		return $string;
	}

	/**
	 * Convert the diff array to an HTML representation.
	 *
	 * @param array  $diff The diff array.
	 * @param string $separator Separator between lines.
	 * @return string The diff HTML.
	 */
	public static function to_html( $diff, $separator = '<br>' ) {
		$html = '';
		foreach ( $diff as $line ) {
			switch ( $line[1] ) {
				case self::UNMODIFIED:
					$element = 'span';
					break;
				case self::DELETED:
					$element = 'del';
					break;
				case self::INSERTED:
					$element = 'ins';
					break;
			}
			$html .= '<' . $element . '>' . htmlspecialchars( $line[0] ) . '</' . $element . '>';
			$html .= $separator;
		}
		return $html;
	}

	/**
	 * Convert the diff array to an HTML table representation.
	 *
	 * @param array  $diff The diff array.
	 * @param string $indentation HTML indentation.
	 * @param string $separator Separator between lines.
	 * @return string The diff table HTML.
	 */
	public static function to_table( $diff, $indentation = '', $separator = '<br>' ) {
		$html = $indentation . "<table class=\"diff\">\n";
		$html .= $indentation . '<tr class="t_head">';
		$html .= '<td><h3>Current Version</h3></td>';
		$html .= '<td><h3>Latest Version</h3></td>';
		$html .= '</tr>';
		$index = 0;
		$diff_count = count( $diff );

		while ( $index < $diff_count ) {
			switch ( $diff[ $index ][1] ) {
				case self::UNMODIFIED:
					$left_cell = self::get_cell_content( $diff, $indentation, $separator, $index, self::UNMODIFIED );
					$right_cell = $left_cell;
					break;
				case self::DELETED:
					$left_cell = self::get_cell_content( $diff, $indentation, $separator, $index, self::DELETED );
					$right_cell = '';
					break;
				case self::INSERTED:
					$left_cell = '';
					$right_cell = self::get_cell_content( $diff, $indentation, $separator, $index, self::INSERTED );
					break;
			}
			$html .= $indentation . "<tr>\n";
			$html .= $indentation . '<td class="diff'
				   . ( $left_cell === $right_cell
					   ? 'Unmodified'
					   : ( '' === $left_cell ? 'Blank' : 'Deleted' ) )
				   . '">'
				   . $left_cell
				   . "</td>\n";
			$html .= $indentation . '    <td class="diff'
				   . ( $left_cell === $right_cell
					   ? 'Unmodified'
					   : ( '' === $right_cell ? 'Blank' : 'Inserted' ) )
				   . '">'
				   . $right_cell
				   . "</td>\n";
			$html .= $indentation . "</tr>\n";
		}
		$html .= $indentation . "</table>\n";
		return $html;
	}

	/**
	 * Get the cell content for HTML table.
	 *
	 * @param array  $diff The diff array.
	 * @param string $indentation HTML indentation.
	 * @param string $separator Separator between lines.
	 * @param int    $index The current index in the diff array.
	 * @param int    $type The type of diff (UNMODIFIED, DELETED, INSERTED).
	 * @return string The cell content.
	 */
	private static function get_cell_content( $diff, $indentation, $separator, &$index, $type ) {
		$html = '';
		$diff_count = count( $diff ); // Store count in a variable.
		while ( $index < $diff_count && $diff[ $index ][1] == $type ) {
			$html .= '<span>' . htmlspecialchars( $diff[ $index ][0] ) . '</span>' . $separator;
			$index++;
		}
		return $html;
	}
}
