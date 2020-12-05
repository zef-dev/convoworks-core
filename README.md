# Convoworks

Convoworks is a PHP framework for managing conversational services (Amazon Alexa skills, Google actions and chatbots over Dialogflow, Viber, FB Messenger ...)
Here are few key features:

* It defines conversation as a stream of reusable components which are manageable through admin API
* It handles communication layer with connected platforms
* It highly relies on PSRs and own interface definitions, so it can be used with any PHP application.


## Overview

There are several logical parts inside Convoworks framework.

### Core interfaces and implementations

This part defines what is service, and what kind of components it can use. It also defines persistence and platform communication layer.

Here are few parts to notice

* `\Convo\Core\Workflow` namespace - defines workflow component types - elements, processors, filters
* `\Convo\Core\Intent` namespace - defines Convoworks intent model
* `\Convo\Core\Params` namespace - defines how the service runtime parameters are accessed or set
* `\Convo\Core\Publisher` namespace - defines how we propagate definition to platforms
* `\Convo\Core\Factory` namespace - defines how the service is loaded and how the components are created
* `\Convo\Core\ConvoServiceInstance` class - the concrete service executor class



### Admin REST

Admin rest allows access and management of the conversation workflow and publishing process. All handlers are placed in `\Convo\Core\Admin` namespace.

There is a Postman definition you can find in the docs folder which describes full API.

### Adapters

Each supported platform has its own folder in the `\Convo\Core\Adapters` which contains several specific implementations required by the Convoworks. Firstly it defines http request handler for accepting platform requests. Then it defines how to create Convoworks request/response objects which are used in service workflow. It also implements how the service definition is propagated to target platforms.


### Default component packages

Convoworks is shipped with a couple of packages which you'll use most of the time.

* **Core** - This package is required in all services. Contains basic components that you will use most of the time. It also contains standard intent and entity definitions, common functions and service templates.
* **Dialogflow** - Google assistant specific components
* **Gnlp** - Google NLP based filtering - Can not be used with Alexa
* **Text** - Plain text based filtering - Can not be used with Alexa
* **Visuals** - Visual conversation elements



## Integration

You can import and run Convoworks with in literally any PHP web application. If your app uses PSR compatible framework and Dependency Injection, than it is even easier.

Convoworks exposes its functionality through admin and public REST apis.
Public API is used by conversational platforms (e.g. Amazon Alexa) and it does not need authentication. Each supported platform has its own handler.
Admin API serves for managing the conversation flow, configurations and release handling. It requires authenticated user requests.

In order to mount Convoworks REST handlers, we have to bootstrap them somehow and we have to provide few implementations required by the system.


### Admin users

Serves for accessing logged user and enables saving some specific user data (e.g. platform access configuration), service ownership and sharing.

Convoworks is accessing user data through `\Convo\Core\IAdminUserDataProvider` interface which returns `\Convo\Core\IAdminUser` objects. You have to implement these two interfaces, because the user management is always system specific.


### Service data storage

Service data layer defines loading/saving service data, managing versions and releases and stores service related, runtime parameters. It is defined with `\Convo\Core\IServiceDataProvider`, `\Convo\Core\Params\IServiceParams` and `\Convo\Core\Params\IServiceParamsFactory` interfaces.

We provided two service data implementations, one stores data on the filesystem - [convoworks-data-filesystem](https://github.com/zef-dev/convoworks-data-filesystem) and one is working with mysql - [convoworks-mypdo](https://github.com/zef-dev/convoworks-mypdo).

### Http factory

Convoworks requires a PSR compatible http client and uses it through the `\Convo\Core\Util\IHttpFactory` interface.
You can use our [convoworks-guzzle](https://github.com/zef-dev/convoworks-guzzle) implementation.


### Bootstrapping 

Although you can manually create all required classes, we recommend using an PSR compatible DI container.


### REST routing

Instead of the mapping each route in your app, you can use grouped handlers which already have subroutine implemented.
Our handlers are always expecting `convo/v1` as a base for all Convoworks requests, so you can route all such requests to the e.g. `\Convo\Core\Admin\AdminRestApi` when you are integrating the admin API.

### Registering packages

Your application will also define which Convoworks custom packages will be available for use.


### Admin API Authentication

Once you have that implemented, you have to make logged user available for REST handlers. Somewhere in the app bootstrap process, set the request attribute `Convo\Core\IAdminUser` to your user (which implements `\Convo\Core\IAdminUser`).

```
$user       =   $this->_adminUserDataProvider->findUser( 'myusernameiremail');
$request    =   $request->withAttribute( \Convo\Core\IAdminUser::class, $user);
```

### Convoworks Prototype

You can check our example integration called Convoworks Prototype. You can [download](https://convoworks.com/downloads/) it and find more information [here](https://convoworks.com/meet-the-convoworks-prototype-plain-php-convoworks-integration-example/)

---

Check for more information on [convoworks.com](https://convoworks.com)

