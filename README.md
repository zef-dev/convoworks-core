# Convoworks

Convoworks is a PHP framework for managing conversational services (Amazon Alexa skills, Google actions and chatbots over Dialogflow, Viber, FB Messenger ...)
Here are few key features:

* It defines conversation as a stream of reusable components which are manageable through admin API
* It handles communication layer with connected platforms
* It highly relies on PSRs and own interface definitions, so it can be used with any PHP application.

## Beta period

Please note that the Convoworks is currently in beta phase and it is planned to be released in March 2021.
In a meanwhile, we will have several releases and some of them could have breaking changes.
 

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

You can use the [Convoworks Editor](https://github.com/zef-dev/convoworks-editor) which is tailored to cover all Convoworks admin api functionalities.

There is a also Postman definition you can find in the docs folder which describes full API.

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

Here is the full ecosystem:

**System utils**

| DI key | Type | Description |
| --- | --- | --- |
| **logger** | `\Psr\Log\LoggerInterface` | Provides logger |
| **httpFactory** | `\Convo\Core\Util\IHttpFactory` | Provides http layer access to the system |
| **currentTimeService** | `\Convo\Core\Util\ICurrentTimeService` | Allows as to have mockable time provider |
| **cache** | `\Psr\SimpleCache\CacheInterface` | Provides access to cache infrastructure |


**Core functionality**
| DI key | Type | Description |
| --- | --- | --- |
| **adminUserDataProvider** | `\Convo\Core\IAdminUserDataProvider` | Allows integration with your own admin users |
| **convoServiceParamsFactory** | `\Convo\Core\Params\IServiceParamsFactory` | For handling runtime parameters |
| **convoServiceDataProvider** | `\Convo\Core\IServiceDataProvider` | Service data persistance layer |
| **serviceMediaManager** | `\Convo\Core\Media\IServiceMediaManager` | Service media storage |
| **convoServiceFactory** | `\Convo\Core\Factory\ConvoServiceFactory` | Loads runnable service instance |
| **platformRequestFactory** | `\Convo\Core\Factory\PlatformRequestFactory` | Updates text based requests to intent based, through delegate platform |
| **packageProviderFactory** | `\Convo\Core\Factory\PackageProviderFactory` | Provides access to concrete package definitions |
| **serviceReleaseManager** | `\Convo\Core\Publish\ServiceReleaseManager` | Updates release and version information |
| **platformPublisherFactory** | `\Convo\Core\Publish\PlatformPublisherFactory` | Provides concrete platform publishers - `\Convo\Core\Publish\IPlatformPublisher` |


**Platform specifics**
| DI key | Type | Description |
| --- | --- | --- |
| **amazonAuthService** | `\Convo\Core\Adapters\Alexa\AmazonAuthService` | Enables authentication for Amazon Alexa SMAPI |
| **alexaRequestValidator** | `\Convo\Core\Adapters\Alexa\Validators\AlexaRequestValidator` | Validates Amazon Alexa requests |
| **amazonPublishingService** | `\Convo\Core\Adapters\Alexa\AmazonPublishingService` | Our SMAPI implementation |
| **dialogflowApiFactory** | `\Convo\Core\Adapters\Dialogflow\DialogflowApiFactory` | Provides access to Dialogflow API |
| **facebookMessengerApiFactory** | `\Convo\Core\Adapters\Fbm\FacebookMessengerApiFactory` | Provides access to FB Messenger API |
| **viberApi** | `\Convo\Core\Adapters\Viber\ViberApi` | Our Viber API implementation |


**Admin API specifics**
| DI key | Type | Description |
| --- | --- | --- |
| **\Convo\Core\Admin\ServicesRestHandler** | `\Convo\Core\Admin\ServicesRestHandler` | Service management |
| **\Convo\Core\Admin\ServiceVersionsRestHandler** | `\Convo\Core\Admin\ServiceVersionsRestHandler` | Service versions and releases management |
| **\Convo\Core\Admin\ServicePlatformConfigRestHandler** | `\Convo\Core\Admin\ServicePlatformConfigRestHandler` | Service platform configurations |
| **\Convo\Core\Admin\UserPlatformConfigRestHandler** | `\Convo\Core\Admin\UserPlatformConfigRestHandler` | Updates user configurations |
| **\Convo\Core\Admin\UserPackgesRestHandler** | `\Convo\Core\Admin\UserPackgesRestHandler` | Access to user packages |
| **\Convo\Core\Admin\ServicePackagesRestHandler** | `\Convo\Core\Admin\ServicePackagesRestHandler` | Service packages management |
| **\Convo\Core\Admin\TemplatesRestHandler** | `\Convo\Core\Admin\TemplatesRestHandler` | Access to available templates |
| **\Convo\Core\Admin\TestServiceRestHandler** | `\Convo\Core\Admin\TestServiceRestHandler` | Service simulator |
| **\Convo\Core\Admin\ServiceImpExpRestHandler** | `\Convo\Core\Admin\ServiceImpExpRestHandler` | Import/export service data |
| **\Convo\Core\Admin\ComponentHelpRestHandler** | `\Convo\Core\Admin\ComponentHelpRestHandler` | Loads component help if available |
| **\Convo\Core\Admin\ConfigurationRestHandler** | `\Convo\Core\Admin\ConfigurationRestHandler` | Loads configuration options |
| **\Convo\Core\Admin\MediaRestHandler** | `\Convo\Core\Admin\MediaRestHandler` | Service media management |
| **propagationErrorReport** | `\Convo\Core\Admin\PropagationErrorReport` | Error reporting utility |

**Public API specifics**
| DI key | Type | Description |
| --- | --- | --- |
| **\Convo\Core\Adapters\ConvoChat\ConvoChatRestHandler** | `\Convo\Core\Adapters\ConvoChat\ConvoChatRestHandler` | Handles web chat requests |
| **\Convo\Core\Adapters\Google\Dialogflow\DialogflowAgentRestHandler** | `\Convo\Core\Adapters\Google\Dialogflow\DialogflowAgentRestHandler` | Handles Dialogflow requests |
| **\Convo\Core\Adapters\Google\Gactions\ActionsRestHandler** | `\Convo\Core\Adapters\Google\Gactions\ActionsRestHandler` | Handles direct Google Actions requests |
| **\Convo\Core\Adapters\Fbm\FacebookMessengerRestHandler** | `\Convo\Core\Adapters\Fbm\FacebookMessengerRestHandler` | Handles Messenger requests |
| **\Convo\Core\Adapters\Viber\ViberRestHandler** | `\Convo\Core\Adapters\Viber\ViberRestHandler` | Handles Viber requests |
| **\Convo\Core\Adapters\Alexa\AlexaSkillRestHandler** | `\Convo\Core\Adapters\Alexa\AlexaSkillRestHandler` | Handles Alexa requests |
| **\Convo\Core\Adapters\Alexa\AmazonAuthRestHandler** | `\Convo\Core\Adapters\Alexa\AmazonAuthRestHandler` | Oauth for enabling Amazon SMAPI access  |
| **\Convo\Core\Adapters\Alexa\CatalogRestHandler** | `\Convo\Core\Adapters\Alexa\CatalogRestHandler` | Exposes entity catalogues to Alexa |
| **\Convo\Core\Media\MediaRestHandler** | `\Convo\Core\Media\MediaRestHandler` | Allows public access to service media |
| **facebookAuthService** | `\Convo\Core\Adapters\Fbm\FacebookAuthService` | Oauth for enabling Facebook API access |



### REST routing

Our handlers are always expecting `convo/v1` as a base for all Convoworks requests, so you can use wildcard to route all such requests to the Convoworks request handlers. Please note that we have two separate REST APIs, public and admin so they are treated and mounted separately.

Instead of mapping each request handler we have, you can use our "grouped" handlers, just one per API. `\Convo\Core\Adapters\PublicRestApi` for public and `\Convo\Core\Admin\AdminRestApi` for admin API.
 Only difference is that in such case you have to use DI container.


### Registering packages

Your application will also define which Convoworks custom packages will be available for use.
Packages are registered by passing `\Convo\Core\Factory\IPackageDescriptor` objects to the `\Convo\Core\Factory\PackageProviderFactory`.

```php
<?php

// example with function based factory
/** @var \Psr\Log\LoggerInterface $logger */
/** @var \Convo\Core\Factory\PackageProviderFactory $packageProviderFactory */
$packageProviderFactory->registerPackage( new FunctionPackageDescriptor('\Convo\Pckg\Trivia\TriviaPackageDefinition', function() use ( $logger, $packageProviderFactory) {
    return new \Convo\Pckg\Trivia\TriviaPackageDefinition(
        $logger, $packageProviderFactory
    );
}));

// example with class based factory - requires DI container!
/** @var \Psr\Container\ContainerInterface $container */
$packageProviderFactory->registerPackage( new ClassPackageDescriptor('\Convo\Pckg\Trivia\TriviaPackageDefinition', $container));
```

### Admin API Authentication

Once you have that implemented, you have to make logged user available for REST handlers. Somewhere in the app bootstrap process, set the request attribute `Convo\Core\IAdminUser` to your user (which implements `\Convo\Core\IAdminUser`).

```php
<?php

$user       =   $this->_adminUserDataProvider->findUser( 'myusernameiremail');
$request    =   $request->withAttribute( \Convo\Core\IAdminUser::class, $user);
```

## Roadmap

* Prefix our intent model on target platforms - It will enable users to manually create additional intents and entities directly on platform and not to be overwritten when model is propagated
* Propagate platform system events - Ability to reference some platform intent or entity in a manner that it will be automatically turned on when we propagate Convoworks intent model
* Component migration interface to packages - Ability to migrate component definitions to the new version. Right now it is on the core level, but should be on the package.
* Package files - ability to deploy files inside component packages to be used in services (mp3 audio prompts, images ...)
* Increase unit test coverage - 


## Convoworks Prototype

You can check our example integration called Convoworks Prototype. You can [download](https://convoworks.com/downloads/) it and find more information [here](https://convoworks.com/meet-the-convoworks-prototype-plain-php-convoworks-integration-example/)


---

For more information, please check out [convoworks.com](https://convoworks.com)


