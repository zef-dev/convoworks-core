# Convoworks

Convoworks is a PHP framework for managing conversational services (Amazon Alexa skills, Google actions and chatbots over Dialogflow, Viber, FB Messenger ...)

* It defines conversation as a stream of reusable components.
* It handles communication layer with connected platforms.
* It highly relies on PSRs and own interface definitions, so it can be used with any PHP application.

Basically Convoworks is a PSR compatibile REST application.

## Overview


### Core interfaces and implementations

This part defines what is service, and what kind of components it can use. It also defines persistance and platform communication layer.


### Admin REST

Admin rest allows access and management of the conversation workflow and publishing process.
There is the Postman definition you can find in docs folder which describes full API.


### Default component packages

Convoworks is shipped with couple of packages which you'll use most of the itme.

* ** Core ** - This package is required in all services. Contains basic components that you will use most of the time. It also contains standard intent and entity definitions, common functions and service templates.
* ** Dialogflow ** - Google assistant specific components
* ** Gnlp ** - Google NLP based filtering - Can not be used with Alexa
* ** Text ** - Plain text based filtering - Can not be used with Alexa
* ** Visuals ** - Visual conversation elements



## Integration

You can import and run Convoworks with in literaly any PHP web application. If your app uses PSR compatibile framework and Dependency Injection, than it is even easier.
Except technical wireing you have to do (bootsrap), you also have to provide few implementations so that Convoworks is able to access users and is able to store service related data.

### Admin users

Serves for accessing logged user and enables saving some specific user data (e.g. platform access configuration), service ownership and sharing.

Convoworks is accessing user data through `\Convo\Core\IAdminUserDataProvider` interface which returns `\Convo\Core\IAdminUser` objects. You have to implement this two interfaces, because the user management is always system specific.


### Service data storage

Service data layer defiens loading/saving service data, managing versions and releases and stores service related, runtime parameters.
We provided two service data implementations, one stores data on the filesystem and one is working with mysql.

### Http factory

You have to provide PSR compatibile http client.
You can use convoworks-guzzle implementation.


### Bootraping 

Although you can manually create all required classes, we recomend using an PSR compatibile DI container.


### REST routing

Instead of the mapping each route in your app, you can use groupped handlers which allready have subrouting implemented.
Our handlers are always expecting `convo/v1` as a base for all Convoworks requests, so you can route all such requests to the e.g. `\Convo\Core\Admin\AdminRestApi` when you are integrating admin API.

### Registering packages

Your application will also define which Convoworks custom packages will be available for use.


### Admin API Authentication

Once you have that implemented, you have to make logged user available for REST handlers. Somwehere in the app bootstrap process, set request attribute `Convo\Core\IAdminUser` to your user (which implements `\Convo\Core\IAdminUser`).

```
$user       =   $this->_adminUserDataProvider->findUser( 'myusernameiremail');
$request    =   $request->withAttribute( \Convo\Core\IAdminUser::class, $user);
```
Check the Convwoworks Prototype application for example.


### Service users - for end user account linking


## Create your own component package


