<?php
/**************************************************************************
* This file is part of the WebIssues Server program
* Copyright (C) 2006 Michał Męciński
* Copyright (C) 2007-2017 WebIssues Team
*
* This program is free software: you can redistribute it and/or modify
* it under the terms of the GNU Affero General Public License as published by
* the Free Software Foundation, either version 3 of the License, or
* (at your option) any later version.
*
* This program is distributed in the hope that it will be useful,
* but WITHOUT ANY WARRANTY; without even the implied warranty of
* MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
* GNU Affero General Public License for more details.
*
* You should have received a copy of the GNU Affero General Public License
* along with this program.  If not, see <http://www.gnu.org/licenses/>.
**************************************************************************/

if ( !defined( 'WI_VERSION' ) ) die( -1 );

/**
* Helper class for handling and rendering web forms.
*
* This class provides support for handling web forms containing various
* type of input fields and hidden fields for both controllers and views.
*
* The view state, which can consist of multiple items of various types,
* is preserved when the form is submitted using an automatically generated
* hidden field. The values of input fields is also preserved after submitting.
* In addition input fields can also be persisted using view state so their
* values are preserved even if they are not visible (for example when
* the page is a wizard consisting of multiple steps).
*
* View state items and regular input fields are bound directly to properties
* of the controller. They can be retrieved and modified using these
* properties. Items and fields must be explicitly declared by the controller
* to avoid security issues.
*
* Each form has an identifier so if mulitple forms are placed on the same
* page they can be easily distinguished.
*
* The controller is responsible for validating the input fields.
* The setError() method can be used for displaying validation errors.
* Errors can be associated with fields and are automatically displayed
* below those fields, they can also be used independently from fields
* to display error messages in arbitrary location.
*
* This class also provides methods for rendering various kinds of input
* fields in the view template. Each field enclosed in a @c div tag with
* appropriate class together with the associated label and error message.
*
* The renderFormOpen() and renderFormClose() methods should be used by the
* view template for the form to be handled correctly.
*/
class System_Web_Form extends System_Web_Base
{
    private $page = null;

    private $formId = null;

    private $fields = array();
    private $viewState = array();
    private $errors = array();
    private $rules = array();

    private $errorHelper = null;

    /**
    * Constructor.
    * @param $formId The unique identifier used to distinguish forms.
    * @param $page The object whose properties will be bound to the fields
    * and view state items, usually the page controller.
    */
    public function __construct( $formId, $page )
    {
        parent::__construct();

        $this->formId = $formId;
        $this->page = $page;
    }

    /**
    * Register a regular input field.
    * @param $key The name of the field.
    * @param $value The optional initial value of the field.
    */
    public function addField( $key, $value = null )
    {
        $this->page->$key = $value;
        $this->fields[ $key ] = null;
        $this->fields[ $key ] =& $this->page->$key;
    }

    /**
    * Register an input field persisted using the view state.
    * @param $key The name of the field.
    * @param $value The optional initial value of the field.
    */
    public function addPersistentField( $key, $value = null )
    {
        $this->page->$key = $value;
        $this->fields[ $key ] = null;
        $this->fields[ $key ] =& $this->page->$key;
        $this->viewState[ $key ] = null;
        $this->viewState[ $key ] =& $this->page->$key;
    }

    /**
    * Register a view state item.
    * @param $key The name of the item.
    * @param $value The optional initial value of the item.
    */
    public function addViewState( $key, $value = null )
    {
        $this->page->$key = $value;
        $this->viewState[ $key ] = null;
        $this->viewState[ $key ] =& $this->page->$key;
    }

    /**
    * Check if the form was submitted and retrieve values of fields and view
    * state items.
    * @return @c true if the form was submitted, @c false otherwise.
    */
    public function loadForm()
    {
        if ( $this->request->getFormField( '__formId' ) != $this->formId )
            return false;

        $serialized = $this->request->getFormField( '__viewState' );
        if ( !empty( $serialized ) )
            $this->loadViewState( $serialized );

        $formFields = $this->request->getFormFields();
        foreach ( $this->fields as $key => &$value ) {
            if ( isset( $formFields[ $key ] ) )
                $value = $formFields[ $key ];
        }

        return true;
    }

    /**
    * Check if the form was submitted using the specified submit button.
    * @param $key The name of the submit button.
    * @return @c true if the form was submitted using that button.
    */
    public function isSubmittedWith( $key )
    {
        return $this->request->getFormField( $key . 'Submit' ) != null;
    }

    /**
    * Set an error message for the given field. The message is displayed in red
    * just below the field with the same name. All Errors are cleared when
    * the form is re-submitted.
    * @param $key The name of the error.
    * @param $error The error message to be displayed.
    */
    public function setError( $key, $error )
    {
        $this->errors[ $key ] = $error;
    }

    /**
    * Return @c true if at least one error was set.
    * @param $key Optional name of the error to check.
    */
    public function hasErrors( $key = null )
    {
        if ( $key != null )
            return isset( $this->errors[ $key ] );
        return !empty( $this->errors );
    }

    /**
    * Clear all validation rules.
    */
    public function clearRules()
    {
        $this->rules = array();
    }

    /**
    * Register a text validation rule.
    * @param $key The name of the field.
    * @param $maxLength The maximum allowed length of the string.
    * @param $flags See System_Api_Parser::normalizeString().
    */
    public function addTextRule( $key, $maxLength = null, $flags = 0 )
    {
        $this->rules[ $key ][ 'text' ] = array( 'max-length' => $maxLength, 'flags' => $flags );
        if ( !( $flags & System_Api_Parser::AllowEmpty ) )
            $this->rules[ $key ][ 'required' ] = true;
    }

    /**
    * Register a select or radio group validation rule.
    * @param $key The name of the field.
    * @param $items The array of allowed items.
    */
    public function addItemsRule( $key, $items )
    {
        $this->rules[ $key ][ 'items' ] = array( 'items' => $items );
        if ( !isset( $items[ '' ] ) )
            $this->rules[ $key ][ 'required' ] = true;
    }

    /**
    * Register a password validation rule.
    * @param $key The name of the field.
    * @param $compareKey The name of the other field to compare.
    */
    public function addPasswordRule( $key, $compareKey )
    {
        $this->rules[ $key ][ 'password' ] = array( 'compare-key' => $compareKey );
    }

    /**
    * Register a required field validation rule. Normally this rule
    * is added automatically by addTextRule or addItemsRule; use this
    * rule to mark a field as required when using custom validation.
    * @param $key The name of the field.
    */
    public function addRequiredRule( $key )
    {
        $this->rules[ $key ][ 'required' ] = true;
    }

    /**
    * Run all registered validation rules.
    */
    public function validate()
    {
        foreach ( $this->rules as $key => $rules ) {
            foreach ( $rules as $type => $rule ) {
                if ( $this->hasErrors( $key ) )
                    break;
                $this->validateRule( $key, $type, $rule );
            }
        }
    }

    /**
    * Return the System_Web_ErrorHelper object associated with this form.
    */
    public function getErrorHelper()
    {
        if ( empty( $this->errorHelper ) )
            $this->errorHelper = new System_Web_ErrorHelper( $this );
        return $this->errorHelper;
    }

    /**
    * Return jQuery selector for the form.
    */
    public function getFormSelector()
    {
        return '#form-' . $this->formId;
    }

    /**
    * Return jQuery selector for the given field.
    */
    public function getFieldSelector( $key )
    {
        return '#field-' . $this->formId . '-' . $key;
    }

    /**
    * Return jQuery selector for the given radio item.
    */
    public function getRadioSelector( $key, $item )
    {
        return '#field-' . $this->formId . '-' . $key . '-' . $item;
    }

    /**
    * Return jQuery selector for the given submit button.
    */
    public function getSubmitSelector( $key )
    {
        return '#field-' . $this->formId . '-' . $key . 'Submit';
    }

    /**
    * Print the opening tag of the form.
    * @param $url An optional URL to which the form is submitted. By default
    * the URL of the current page is used.
    * @param $attributes Optional array of attributes to be added
    */
    public function renderFormOpen( $url = null, $attributes = array() )
    {
        if ( empty( $url ) )
            $url = $this->mergeQueryString( WI_SCRIPT_URL );
        else
            $url = $this->url( $url );
        $id = 'form-' . $this->formId;
        $this->renderTag( 'form', array_merge( array( 'action' => $url, 'method' => 'post', 'accept-charset' => 'UTF-8', 'id' => $id ), $attributes ), true );
        echo '<div>';
        $this->renderInput( 'hidden', '__formId', $this->formId );
    }

    /**
    * Print the closing tag of the form.
    */
    public function renderFormClose()
    {
        if ( !empty( $this->viewState ) )
            $this->renderInput( 'hidden', '__viewState', $this->saveViewState() );
        echo "</div></form>\n";
    }

    /**
    * Render a regular text input field.
    * @param $label The optional label for the field.
    * @param $key The name of the field.
    * @param $attributes Optional array of attributes to be added
    * to the @c input tag.
    */
    public function renderText( $label, $key, $attributes = array() )
    {
        echo "<div class=\"form-field\">\n";
        $this->renderLabel( $label, $key );
        $this->renderInput( 'text', $key, $this->getValue( $key ), $attributes );
        $this->renderError( $key );
        echo "</div>\n";

        if ( isset( $this->viewState[ $key ] ) )
            $this->viewState[ $key ] = null;
    }

    /**
    * Render a password input field.
    * @param $label The optional label for the field.
    * @param $key The name of the field.
    * @param $attributes Optional array of attributes to be added
    * to the @c input tag.
    */
    public function renderPassword( $label, $key, $attributes = array() )
    {
        echo "<div class=\"form-field\">\n";
        $this->renderLabel( $label, $key );
        $this->renderInput( 'password', $key, $this->getValue( $key ), $attributes );
        $this->renderError( $key );
        echo "</div>\n";

        if ( isset( $this->viewState[ $key ] ) )
            $this->viewState[ $key ] = null;
    }

    /**
    * Render multiple mutually exclusive radio items.
    * @param $key The name of the field.
    * @param $items Array containing item identifiers as keys and labels
    * as values.
    * @param $attributes Optional array of attributes to be added
    * to the @c input tags.
    */
    public function renderRadioGroup( $key, $items, $attributes = array() )
    {
        echo "<div class=\"form-radiogroup\">\n";
        foreach( $items as $item => $label )
            $this->renderRadio( $label, $key, $item, $attributes );
        $this->renderError( $key );
        echo "</div>\n";

        if ( isset( $this->viewState[ $key ] ) )
            $this->viewState[ $key ] = null;
    }

    /**
    * Render a single radio item.
    * @param $label The label of the item.
    * @param $key The name of the field.
    * @param $item The identifier of the item.
    * @param $attributes Optional array of attributes to be added
    * to the @c input tag.
    */
    public function renderRadio( $label, $key, $item, $attributes = array() )
    {
        echo "<div class=\"form-radio\">\n";
        $id = 'field-' . $this->formId . '-' . $key . '-' . $item;
        $this->renderInput( 'radio', $key, $item, array_merge( array( 'id' => $id, 'checked' => !strcmp( $this->getValue( $key ), $item ) ), $attributes ) );
        $this->renderLabel( $label, $key, $id, false );
        echo "</div>\n";
    }

    /**
    * Render a check box.
    * @param $label The optional label for the check box.
    * @param $key The name of the field.
    * @param $attributes Optional array of attributes to be added
    * to the @c input tag.
    */
    public function renderCheckBox( $label, $key, $attributes = array() )
    {
        echo "<div class=\"form-checkbox\">\n";
        $this->renderInput( 'checkbox', $key, '1', array_merge( array( 'checked' => ( $this->getValue( $key ) == true ) ), $attributes ) );
        $this->renderLabel( $label, $key, null, false );
        $this->renderError( $key );
        echo "</div>\n";

        // this is necessary for un-checking a persisted checkbox to work
        if ( isset( $this->viewState[ $key ] ) )
            $this->viewState[ $key ] = null;
    }

    /**
    * Render a field for uploading files. The field cannot be bound
    * to a property but the file can be retrieved using the
    * System_Core_Request::getUploadedFile() method.
    * @param $label The optional label for the field.
    * @param $key The name of the field.
    * @param $attributes Optional array of attributes to be added
    * to the @c input tag.
    */
    public function renderFile( $label, $key, $attributes = array() )
    {
        echo "<div class=\"form-field\">\n";
        $this->renderLabel( $label, $key );
        $this->renderInput( 'file', $key, null, $attributes );
        $this->renderError( $key );
        echo "</div>\n";
    }

    /**
    * Render a drop-down selection input field.
    * @param $label The optional label for the field.
    * @param $key The name of the field.
    * @param $items Array containing item identifiers as keys and labels
    * as values. Nested arrays can be used to generate options groups.
    * @param $attributes Optional array of attributes to be added
    * to the @c input tag.
    */
    public function renderSelect( $label, $key, $items, $attributes = array() )
    {
        echo "<div class=\"form-field\">\n";
        $id = 'field-' . $this->formId . '-' . $key;
        $currentValue = $this->getValue( $key );
        $this->renderLabel( $label, $key, $id );
        $this->renderTag( 'select', array_merge( array( 'name' => $key, 'id' => $id ), $attributes ), true );
        foreach ( $items as $itemKey => $itemValue ) {
            if ( is_array( $itemValue ) || is_object( $itemValue ) && is_a( $itemValue, 'Iterator' ) ) {
                $this->renderTag( 'optgroup', array( 'label' => $itemKey ), true );
                foreach ( $itemValue as $subItemKey => $subItemValue )
                    $this->renderTag( 'option', array( 'value' => $subItemKey, 'selected' => !strcmp( $currentValue, $subItemKey ) ), $subItemValue );
                echo "</optgroup>\n";
            } else {
                $this->renderTag( 'option', array( 'value' => $itemKey, 'selected' => !strcmp( $currentValue, $itemKey ) ), $itemValue );
            }
        }
        echo "</select>\n";
        $this->renderError( $key );
        echo "</div>\n";

        if ( isset( $this->viewState[ $key ] ) )
            $this->viewState[ $key ] = null;
    }

    /**
    * Render a text area field.
    * @param $label The optional label for the field.
    * @param $key The name of the field.
    * @param $attributes Optional array of attributes to be added
    * to the @c textarea tag.
    */
    public function renderTextArea( $label, $key, $attributes = array() )
    {
        echo "<div class=\"form-field\">\n";
        $id = 'field-' . $this->formId . '-' . $key;
        $this->renderLabel( $label, $key, $id );
        $this->renderTag( 'textarea', array_merge( array( 'name' => $key, 'id' => $id ), $attributes ), $this->getValue( $key ) );
        $this->renderError( $key );
        echo "</div>\n";

        if ( isset( $this->viewState[ $key ] ) )
            $this->viewState[ $key ] = null;
    }

    /**
    * Render a submit button.
    * @param $label The label for the button.
    * @param $key The name of the button.
    * @param $attributes Optional array of attributes to be added
    * to the @c input tag.
    */
    public function renderSubmit( $label, $key, $attributes = array() )
    {
        $this->renderInput( 'submit', $key . 'Submit', $label, array_merge( array( 'class' => 'form-button' ), $attributes ) );
    }

    /**
    * Render an error message if it was set. It is rendered using a paragraph
    * with @c error class.
    * @param $key The name of the error message.
    */
    public function renderError( $key )
    {
        if ( !empty( $this->errors[ $key ] ) )
            echo "<p class=\"error\">" . $this->errors[ $key ] . "</p>\n";
    }

    private function renderLabel( $label, $key, $id = null, $markRequired = true )
    {
        if ( !empty( $label ) ) {
            if ( empty( $id ) )
                $id = 'field-' . $this->formId . '-' . $key;
            if ( $markRequired && $this->getRule( $key, 'required' ) )
                $label .= " <span class=\"required\">*</span>";
            echo "<label for=\"$id\">$label</label>\n";
        }
    }

    private function renderInput( $type, $key, $value, $attributes = array() )
    {
        $id = 'field-' . $this->formId . '-' . $key;
        $maxLength = null;
        if ( $type == 'text' || $type == 'password' ) {
            $rule = $this->getRule( $key, 'text' );
            if ( $rule != null )
                $maxLength = $rule[ 'max-length' ];
        }
        $this->renderTag( 'input', array_merge( array( 'type' => $type, 'name' => $key, 'id' => $id, 'value' => $value, 'maxlength' => $maxLength ), $attributes ) );
    }

    private function renderTag( $name, $attributes, $text = null )
    {
        echo $this->buildTag( $name, $attributes, $text );
    }

    private function loadViewState( $serialized )
    {
        $loadedState = unserialize( base64_decode( $serialized ) );
        foreach ( $this->viewState as $key => &$value ) {
            if ( isset( $loadedState[ $key ] ) )
                $value = $loadedState[ $key ];
        }
    }

    private function saveViewState()
    {
        return base64_encode( serialize( $this->viewState ) );
    }

    private function getValue( $key )
    {
        return isset( $this->fields[ $key ] ) ? System_Web_Escaper::wrap( $this->fields[ $key ] ) : null;
    }

    private function validateRule( $key, $type, $rule )
    {
        $field =& $this->fields[ $key ];

        switch ( $type ) {
            case 'text':
                $parser = new System_Api_Parser();
                try {
                    $field = $parser->normalizeString( $field, $rule[ 'max-length' ], $rule[ 'flags' ] );
                } catch ( System_Api_Error $ex ) {
                    $this->getErrorHelper()->handleError( $key, $ex );
                }
                break;

            case 'items':
                $match = false;
                foreach ( $rule[ 'items' ] as $itemKey => $itemValue ) {
                    if ( is_array( $itemValue ) ) {
                        foreach ( $itemValue as $subItemKey => $subItemValue ) {
                            if ( !strcmp( $field, $subItemKey ) )
                                $match = true;
                        }
                    } else {
                        if ( !strcmp( $field, $itemKey ) )
                            $match = true;
                    }
                }
                if ( !$match )
                    $this->getErrorHelper()->handleError( $key, System_Api_Error::NoMatchingItem );
                break;

            case 'password':
                $compareKey = $rule[ 'compare-key' ];
                if ( !$this->hasErrors( $compareKey ) && $field !== $this->fields[ $compareKey ] )
                    $this->getErrorHelper()->handleError( $key, System_Api_Error::PasswordNotMatching );
                break;
        }
    }

    private function getRule( $key, $type )
    {
        return isset( $this->rules[ $key ][ $type ] ) ? $this->rules[ $key ][ $type ] : null;
    }
}
