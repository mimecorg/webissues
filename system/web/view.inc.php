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
* View associated with a component.
*
* The view object loads and renders a template file which is a regular
* PHP file with HTML markup and PHP statements (using the alternative
* syntax by convention).
*
* A view can be decorated by another component with an associated view,
* which in turn can be decorated by yet another component. This way
* common layout templates can be created. Components can pass information
* to their decorators using slot variables, for example the page title
* can be passed from the page component to the outermost decorator.
*
* A view can also include another component with its view. This way reusable
* page elements can be created and complex pages can be divided into
* separate components. The nested component can also be decorated and can
* include other components on its own.
*
* Within the template file, the component object and its methods are
* accessible using $this and dynamic properties of the component are
* accessible as local variables with special HTML characters automatically
* escaped using System_Web_Escaper.
*
* @see System_Web_Component
*/
class System_Web_View extends System_Web_Base
{
    protected $template = null;
    protected $componentClass = null;
    protected $parentView = null;

    protected $decoratorClass = null;

    protected $data = null;
    protected $content = null;

    protected $slots = array();

    private $currentSlot = null;

    /**
    * Constructor. Views are created by components using the
    * System_Web_Component::createView() method.
    * @param $template Path of the view template file (without extension).
    * @param $componentClass Name of the component class.
    * @param $parentView Optional parent view.
    */
    public function __construct( $template, $componentClass, $parentView = null )
    {
        parent::__construct();

        $this->template = $template;
        $this->componentClass = $componentClass;
        $this->parentView = $parentView;

        if ( $parentView != null )
            $this->slots =& $parentView->slots;
    }

    /**
    * Set the class name of the decorator component.
    * The decorator is executed while rendering the view and the
    * contents of the current view can be inserted into the
    * decorator using insertContent().
    */
    public function setDecoratorClass( $decoratorClass )
    {
        $this->decoratorClass = $decoratorClass;
    }

    /**
    * Set the data from dynamic properties of the component. This method
    * is called automatically by System_Web_Component::prepareView().
    */
    public function setData( $data )
    {
        $this->data = $data;
    }

    /**
    * Set the content of the decorated view. This method is called
    * automatically when rendering a view with a decorator component.
    */
    public function setContent( $content )
    {
        $this->content = $content;
    }

    /**
    * Propagate the slot variables from the decorated view. This method is called
    * automatically when rendering a view with a decorator component.
    */
    public function setSlots( $slots )
    {
        $this->slots = $slots;
    }

    /**
    * Set the value of a slot which is propagated to decorators.
    * @param $key The name of the slot variable.
    * @param $value The value of the variable.
    */
    public function setSlot( $key, $value )
    {
        $this->slots[ $key ] = $value;
    }

    /**
    * Append the item to the slot.
    * @param $key The name of the slot variable.
    * @param $item The item to append.
    */
    public function appendSlotItem( $key, $item )
    {
        $this->slots[ $key ][] = $item;
    }

    /**
    * Append the item to the slot if it doesn't already exist.
    * @param $key The name of the slot variable.
    * @param $item The item to merge.
    */
    public function mergeSlotItem( $key, $item )
    {
        if ( empty( $this->slots[ $key ] ) || array_search( $item, $this->slots[ $key ] ) === false )
            $this->slots[ $key ][] = $item;
    }

    /**
    * Return the value of the slot variable.
    * @param $key The name of the slot variable.
    * @param $default The default value if the variable was not specified.
    * @return The value of the variable.
    */
    public function getSlot( $key, $default = null )
    {
        return isset( $this->slots[ $key ] ) ? $this->slots[ $key ] : $default;
    }

    /**
    * Check if the slot variable is specified.
    * @param $key The name of the slot variable.
    * @return @c true if the variable is specified.
    */
    public function hasSlot( $key )
    {
        return isset( $this->slots[ $key ] );
    }

    /**
    * Insert the content of the slot variable. Appropriate escaping is applied
    * so this method should be used in views instead of "echo getSlot".
    */
    public function insertSlot( $key )
    {
        if ( isset( $this->slots[ $key ] ) )
            echo System_Web_Escaper::wrap( $this->slots[ $key ] );
    }

    /**
    * Begin rendering the content of a slot. All content between beginSlot
    * and endSlot is assigned to the given slot variable.
    * @param $key The name of the slot variable.
    */
    public function beginSlot( $key )
    {
        $this->currentSlot = $key;
        ob_start();
    }

    /**
    * End rendering the content of a slot.
    * @see beginSlot
    */
    public function endSlot()
    {
        $this->slots[ $this->currentSlot ] = new System_Web_RawValue( ob_get_clean() );
        $this->currentSlot = null;
    }

    /**
    * Return the parent view or @c null if the view has no parent.
    */
    public function getParentView()
    {
        return $this->parentView;
    }

    /**
    * Execute the template and decorate it if necessary.
    * @return The rendered content of the view's template.
    */
    public function render()
    {
        $content = $this->executeTemplate();

        if ( $this->decoratorClass != null ) {
            $decorator = System_Web_Component::createComponent( $this->decoratorClass );
            $decorator->getView()->setContent( $content );
            $decorator->getView()->setSlots( $this->slots );
            $content = $decorator->run();
        }

        return $content;
    }

    /**
    * Insert the content of the decorated view. This method should be used
    * in the template of a decorator's view.
    */
    protected function insertContent()
    {
        echo $this->content;
    }

    /**
    * Insert another component into the view.
    * @param $componentClass The class name of the component to insert.
    * @param $parameter Optional parameter passed to the component's
    * constructor.
    */
    protected function insertComponent( $componentClass, $parameter = null )
    {
        $component = System_Web_Component::createComponent( $componentClass, null, $parameter, $this );
        echo $component->run();
    }

    /**
    * Return a translated version of the source string.
    * The original string is returned if no translation is available.
    * Parameter placeholders (%%1, %%2, etc.) are replaced with additional
    * arguments passed to this function.
    * This method calls System_Core_Translator::translate() with appropriate
    * context based on the component's class name.
    * @param $source The source string to translate.
    * @param $comment An optional comment explaining the use of the string
    * to the translators.
    */
    protected function tr( $source, $comment = null )
    {
        $args = func_get_args();
		return $this->translator->translate( System_Core_Translator::UserLanguage, $this->componentClass, $args );
    }

    /**
    * Return the unescaped value of the data item.
    * @param $key The name of the data item.
    * @return The value of the item without HTML escaping.
    */
    protected function getRawValue( $key )
    {
        return isset( $this->data[ $key ] ) ? $this->data[ $key ] : null;
    }

    private function executeTemplate()
    {
        ob_start();

        foreach ( $this->data as $name => $value )
            $$name = System_Web_Escaper::wrap( $value );

        include( WI_ROOT_DIR . '/' . $this->template . '.html.php' );

        return ob_get_clean();
    }
}
