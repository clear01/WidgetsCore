Briefing
--------

This package provides an abstract model of widgets. Widgets are components that can be arranged and used by users.
**This is not out of box solution**. Look at the Clear01/BootstrapWidgetControl and Clear01/DoctrineWidgetsAdapter combination for working solution example.

Supported features are listed below:
- API for presentation layer
- Widget filtering based on context
- Robust model of widget declarations
    - Uniqueness flag (widgets can be added multiple times)
    - Lazy widget instance creation
- User widget configuration persistence

Because the model is abstract, following components must be implemented:

- Persistence layer
- Framework-related layer
- Presentation layer

See ```\Clear01\Widgets\IWidgetManager``` for understanding the API that will be used by your app.

Layers to implement
-------------------

### Persistence layer
This package also provides support of saving and loading user widget configurations.
Multiple widget attributes are persisted.

The model works with ```\Clear01\Widgets\IWidgetRecord``` interface, which defines widget instance entity.
Persistence itself is maintained by ```\Clear01\Widgets\IWidgetPersister```. Both of the interfaces must be implemented in your persistence layer.

For working example, see the [```clear01/doctrine-widget-adapter```](http://github.com/Clear01/DoctrineWidgetAdapter) package.

### Framework-related layer
This bridge layer should implement operations such as widget (component) state serialization or retrieving user ids.
#### Component state serialization
This package assumes presence of some sort of UI component model in your app. Because the widget state persistence functionality is covered by this package,
there is ```\Clear01\Widgets\IComponentStateSerializer``` interface available. Implementation of this interface is supposed to save and restore UI component state.
#### User id retrieval
Widgets are supposed to be used on per-user basis. Interface ```\Clear01\Widgets\IUserIdentityAccessor``` is used for retrieving ID of currently logged user.

For working example, see the [```clear01/nette-widgets```](http://github.com/Clear01/NetteWidgets) package.

### Presentation layer
The last layer, represented by a component that will handle user interactions and rendering. Implementation is
completely up to you, ```\Clear01\Widgets\IWidgetManager``` should be used as single dependency from this package.

For working example, take a look at the [```clear01/bootstrap-nette-widget-control```](http://github.com/Clear01/BootstrapNetteWidgetControl) package.
