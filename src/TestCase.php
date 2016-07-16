<?php
namespace Johnbillion\DocsStandards;

abstract class TestCase extends \PHPUnit_Framework_TestCase {

	abstract protected function getTestFunctions();

	abstract protected function getTestClasses();

	/**
	 * Test a function or method for a given class
	 *
	 * @dataProvider dataReflectionTestFunctions
	 *
	 * @param string|array $function The function name, or array of class name and method name.
	 */
	public function testFunction( $function ) {

		// We can't pass Reflector objects in here because they get printed out as the
		// data set when a test fails

		if ( is_array( $function ) ) {
			$ref  = new \ReflectionMethod( $function[0], $function[1] );
			$name = $function[0] . '::' . $function[1] . '()';
		} else {
			$ref  = new \ReflectionFunction( $function );
			$name = $function . '()';
		}

		$docblock      = new \phpDocumentor\Reflection\DocBlock( $ref );
		$doc_comment   = $ref->getDocComment();
		$method_params = $ref->getParameters();
		$doc_params    = $docblock->getTagsByName( 'param' );

		$this->assertNotFalse( $doc_comment, sprintf(
			'The docblock for `%s` should not be missing.',
			$name
		) );

		$this->assertNotEmpty( $docblock->getShortDescription(), sprintf(
			'The docblock description for `%s` should not be empty.',
			$name
		) );

		$this->assertSame( count( $method_params ), count( $doc_params ), sprintf(
			'The number of @param docs for `%s` should match its number of parameters.',
			$name
		) );

		// @TODO check description ends in full stop

		foreach ( $method_params as $i => $param ) {

			$param_doc   = $doc_params[ $i ];
			$description = $param_doc->getDescription();
			$content     = $param_doc->getContent();

			// @TODO decide how to handle variadic functions
			// ReflectionParameter::isVariadic — Checks if the parameter is variadic

			$is_hash = ( ( 0 === strpos( $description, '{' ) ) && ( ( strlen( $description ) - 1 ) === strrpos( $description, '}' ) ) );

			if ( $is_hash ) {
				$lines = explode( "\n", $description );
				$description = $lines[1];
			}

			$this->assertNotEmpty( $description, sprintf(
				'The @param description for the `%s` parameter of `%s` should not be empty.',
				$param_doc->getVariableName(),
				$name
			) );

			list( $param_doc_type, $param_doc_name ) = preg_split( '#\s+#', $param_doc->getContent() );

			$this->assertSame( '$' . $param->getName(), $param_doc_name, sprintf(
				'The @param name for the `%s` parameter of `%s` is incorrect.',
				'$' . $param->getName(),
				$name
			) );

			if ( $param->isArray() ) {
				$this->assertNotFalse( strpos( $param_doc_type, 'array' ), sprintf(
					'The @param type hint for the `%s` parameter of `%s` should state that it accepts an array.',
					$param_doc->getVariableName(),
					$name
				) );
			}

			if ( ( $param_class = $param->getClass() ) && ( 'stdClass' !== $param_class->getName() ) ) {
				$this->assertNotFalse( strpos( $param_doc_type, $param_class->getName() ), sprintf(
					'The @param type hint for the `%s` parameter of `%s` should state that it accepts an object of type `%s`.',
					$param_doc->getVariableName(),
					$name,
					$param_class->getName()
				) );
			}

			$this->assertFalse( strpos( $param_doc_type, 'callback' ), sprintf(
				'`callback` is not a valid type. `callable` should be used in the @param type hint for the `%s` parameter of `%s` instead.',
				$param_doc->getVariableName(),
				$name
			) );

			if ( $param->isCallable() ) {
				$this->assertNotFalse( strpos( $param_doc_type, 'callable' ), sprintf(
					'The @param type hint for the `%s` parameter of `%s` should state that it accepts a callable.',
					$param_doc->getVariableName(),
					$name
				) );
			}

			if ( $param->isOptional() ) {
				$this->assertNotFalse( strpos( $description, 'Optional.' ), sprintf(
					'The @param description for the optional `%s` parameter of `%s` should state that it is optional.',
					$param_doc->getVariableName(),
					$name
				) );
			} else {
				$this->assertFalse( strpos( $description, 'Optional.' ), sprintf(
					'The @param description for the required `%s` parameter of `%s` should not state that it is optional.',
					$param_doc->getVariableName(),
					$name
				) );
			}

			if ( $param->isDefaultValueAvailable() && ( array() !== $param->getDefaultValue() ) ) {
				$this->assertNotFalse( strpos( $description, 'Default ' ), sprintf(
					'The @param description for the `%s` parameter of `%s` should state its default value.',
					$param_doc->getVariableName(),
					$name
				) );
			} else {
				$this->assertFalse( strpos( $description, 'Default ' ), sprintf(
					'The @param description for the `%s` parameter of `%s` should not state a default value.',
					$param_doc->getVariableName(),
					$name
				) );
			}

		}

	}

	public function dataReflectionTestFunctions() {

		$data = array();

		foreach ( $this->getTestFunctions() as $function ) {

			if ( ! function_exists( $function ) ) {
				$this->fail( sprintf( 'The function `%s` doesn\'t exist.', $function ) );
			}

			$data[] = array(
				$function,
			);

		}

		foreach ( $this->getTestClasses() as $class ) {

			if ( ! class_exists( $class ) ) {
				$this->fail( sprintf( 'The class `%s` doesn\'t exist.', $class ) );
			}

			$class_ref = new \ReflectionClass( $class );

			foreach ( $class_ref->getMethods() as $method_ref ) {

				$data[] = array(
					array(
						$class,
						$method_ref->getName(),
					),
				);

			}

		}

		return $data;

	}

}
