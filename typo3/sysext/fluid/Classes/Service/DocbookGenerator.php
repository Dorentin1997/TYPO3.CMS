<?php

/*                                                                        *
 * This script belongs to the FLOW3 package "Fluid".                      *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License as published by the *
 * Free Software Foundation, either version 3 of the License, or (at your *
 * option) any later version.                                             *
 *                                                                        *
 * This script is distributed in the hope that it will be useful, but     *
 * WITHOUT ANY WARRANTY; without even the implied warranty of MERCHAN-    *
 * TABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU Lesser       *
 * General Public License for more details.                               *
 *                                                                        *
 * You should have received a copy of the GNU Lesser General Public       *
 * License along with the script.                                         *
 * If not, see http://www.gnu.org/licenses/lgpl.html                      *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

/**
 * XML Schema (XSD) Generator. Will generate an XML schema which can be used for autocompletion
 * in schema-aware editors like Eclipse XML editor.
 *
 * @version $Id: DocbookGenerator.php 3835 2010-02-22 15:15:17Z robert $
 * @package Fluid
 * @subpackage Service
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 */
class Tx_Fluid_Service_DocbookGenerator extends Tx_Fluid_Service_AbstractGenerator {

	/**
	 * Generate the XML Schema definition for a given namespace.
	 *
	 * @param string $namespace Namespace identifier to generate the XSD for, without leading Backslash.
	 * @return string XML Schema definition
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	public function generateDocbook($namespace) {
		if (substr($namespace, -1) !== Tx_Fluid_Fluid::NAMESPACE_SEPARATOR) {
			$namespace .= Tx_Fluid_Fluid::NAMESPACE_SEPARATOR;
		}

		$classNames = $this->getClassNamesInNamespace($namespace);

		$xmlRootNode = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?>
<section version="5.0" xmlns="http://docbook.org/ns/docbook"
         xml:id="fluid.usermanual.standardviewhelpers"
         xmlns:xl="http://www.w3.org/1999/xlink"
         xmlns:xi="http://www.w3.org/2001/XInclude"
         xmlns:xhtml="http://www.w3.org/1999/xhtml"
         xmlns:svg="http://www.w3.org/2000/svg"
         xmlns:ns="http://docbook.org/ns/docbook"
         xmlns:mathml="http://www.w3.org/1998/Math/MathML">
    <title>Standard View Helper Library</title>

    <para>Should be autogenerated from the tags.</para>
</section>');

		foreach ($classNames as $className) {
			$this->generateXmlForClassName($className, $namespace, $xmlRootNode);
		}

		return $xmlRootNode->asXML();
	}

	/**
	 * Generate the XML Schema for a given class name.
	 *
	 * @param string $className Class name to generate the schema for.
	 * @param string $namespace Namespace prefix. Used to split off the first parts of the class name.
	 * @param SimpleXMLElement $xmlRootNode XML root node where the xsd:element is appended.
	 * @return void
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	protected function generateXmlForClassName($className, $namespace, SimpleXMLElement $xmlRootNode) {
		$reflectionClass = new Tx_Extbase_Reflection_ClassReflection($className);
		if (!$reflectionClass->isSubclassOf($this->abstractViewHelperReflectionClass)) {
			return;
		}

		$tagName = $this->getTagNameForClass($className, $namespace);

		$docbookSection = $xmlRootNode->addChild('section');

		$docbookSection->addChild('title', $tagName);
		$this->docCommentParser->parseDocComment($reflectionClass->getDocComment());
		$this->addDocumentation($this->docCommentParser->getDescription(), $docbookSection);

		$argumentsSection = $docbookSection->addChild('section');
		$argumentsSection->addChild('title', 'Arguments');
		$this->addArguments($className, $argumentsSection);

		return $docbookSection;
	}

	/**
	 * Add attribute descriptions to a given tag.
	 * Initializes the view helper and its arguments, and then reads out the list of arguments.
	 *
	 * @param string $className Class name where to add the attribute descriptions
	 * @param SimpleXMLElement $docbookSection DocBook section to add the attributes to.
	 * @return void
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	protected function addArguments($className, SimpleXMLElement $docbookSection) {
		$viewHelper = $this->instanciateViewHelper($className);
		$argumentDefinitions = $viewHelper->prepareArguments();

		if (count($argumentDefinitions) === 0) {
			$docbookSection->addChild('para', 'No arguments defined.');
			return;
		}
		$argumentsTable = $docbookSection->addChild('table');
		$argumentsTable->addChild('title', 'Arguments');
		$tgroup = $argumentsTable->addChild('tgroup');
		$tgroup['cols'] = 4;
		$this->addArgumentTableRow($tgroup->addChild('thead'), 'Name', 'Type', 'Required', 'Description', 'Default');

		$tbody = $tgroup->addChild('tbody');

		foreach ($argumentDefinitions as $argumentDefinition) {
			$this->addArgumentTableRow($tbody, $argumentDefinition->getName(), $argumentDefinition->getType(), ($argumentDefinition->isRequired()?'yes':'no'), $argumentDefinition->getDescription(), $argumentDefinition->getDefaultValue());
		}
	}

	/**
	 * Instantiate a view helper.
	 *
	 * @param string $className
	 * @return object
	 */
	protected function instanciateViewHelper($className) {
		return $this->objectManager->get($className);
	}

	/**
	 * @param SimpleXMLElement $parent
	 * @param string $name
	 * @param string $type
	 * @param boolean $required
	 * @param string $description
	 * @param string $default
	 * @return void
	 */
	private function addArgumentTableRow(SimpleXMLElement $parent, $name, $type, $required, $description, $default) {
		$row = $parent->addChild('row');

		$row->addChild('entry', $name);
		$row->addChild('entry', $type);
		$row->addChild('entry', $required);
		$row->addChild('entry', $description);
		$row->addChild('entry', (string)$default);
	}

	/**
	 * Add documentation XSD to a given XML node
	 *
	 * As Eclipse renders newlines only on new <xsd:documentation> tags, we wrap every line in a new
	 * <xsd:documentation> tag.
	 * Furthermore, eclipse strips out tags - the only way to prevent this is to have every line wrapped in a
	 * CDATA block AND to replace the < and > with their XML entities. (This is IMHO not XML conformant).
	 *
	 * @param string $documentation Documentation string to add.
	 * @param SimpleXMLElement $docbookSection Node to add the documentation to
	 * @return void
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	protected function addDocumentation($documentation, SimpleXMLElement $docbookSection) {
		$splitRegex = '/^\s*(=[^=]+=)$/m';
		$regex = '/^\s*(=([^=]+)=)$/m';

		$matches = preg_split($splitRegex, $documentation, -1,  PREG_SPLIT_NO_EMPTY  |  PREG_SPLIT_DELIM_CAPTURE );

		$currentSection = $docbookSection;
		foreach ($matches as $singleMatch) {
			if (preg_match($regex, $singleMatch, $tmp)) {
				$currentSection = $docbookSection->addChild('section');
				$currentSection->addChild('title', trim($tmp[2]));
			} else {
				$this->addText(trim($singleMatch), $currentSection);
			}
		}
	}

	/**
	 * @param string $text
	 * @param SimpleXMLElement $parentElement
	 */
	protected function addText($text, SimpleXMLElement $parentElement) {
		$splitRegex = '/
		(<code(?:.*?)>
			(?:.*?)
		<\/code>)/xs';

		$regex = '/
		<code(.*?)>
			(.*?)
		<\/code>/xs';
		$matches = preg_split($splitRegex, $text, -1,  PREG_SPLIT_NO_EMPTY  |  PREG_SPLIT_DELIM_CAPTURE );
		foreach ($matches as $singleMatch) {

			if (preg_match($regex, $singleMatch, $tmp)) {
				preg_match('/title="([^"]+)"/', $tmp[1], $titleMatch);

				$example = $parentElement->addChild('example');
				if (count($titleMatch)) {
					$example->addChild('title', trim($titleMatch[1]));
				} else {
					$example->addChild('title', 'Example');
				}
				$this->addChildWithCData($example, 'programlisting', trim($tmp[2]));
			} else {
				$textParts = explode("\n", $singleMatch);
				foreach ($textParts as $text) {
					if (trim($text) === '') continue;
					$this->addChildWithCData($parentElement, 'para', trim($text));
				}
			}
		}
	}
}
?>