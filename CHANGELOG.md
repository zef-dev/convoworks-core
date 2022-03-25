# CHANGELOG

## [Current development]
## [Releases]

# 0.22.12.1 - 2022-03-25
* Quickfix for splitting example phrases in Alexa configuration
  * Make splitting backwards compatible with the old method of splitting at semicolons

## 0.22.12 - 2022-03-24
* Required slots now have a special editor type
* Split example phrases by line and trim them
* Update standard for describing status variables
* New element - Element Queue
* Accept template when creating service from file
* Fix exception type in AlexaRemindersApi

## 0.22.11 - 2022-02-16
* Add support for Alexa Reminders API
* Add new expression function `parse_date_time` which interpolates incoming date formats provided by platform slots
* Expose service contexts inside service runtime -- accessible as `${contexts[contextId]}`
* Accept template file on service import
* Add ability to download service as a template
* Various bugfixes and improvements

## 0.22.10.2 - 2022-01-24
* Pre-dispatch flow only in session start block (fix wrong interface allowed)

## 0.22.10.1 - 2022-01-24
* Allow pre-dispatch flow only in session start block

## 0.22.10.0 - 2022-01-21
* Added a new block role - error handler
* Added a new flow for conversation blocks - pre-dispatch
* Fixed finding default block in service
* Added proxy classes for certain Symfony components
* Added new functions `date_tz` and `strtotime_tz`
* Rework `GetAmazonCustomerProfileElement` to read permissions from Amazon Platform Config
* Added Alexa Skill Certification status check
* Fixed Google Actions publisher

## 0.22.9.0 - 2021-12-23
* Added a new block role - default fallback
* Added support for evaluating parameters as string
* `LoopElement` now uses an iterator
* Add support to mark output text as both standard text and reprompt
* Add support for `context_id` editor type
* Fixed empty text response issue

## 0.22.8.0 - 2021-12-03
* Fixed how intent readers accept incoming intent requests
* Fixed `AplUserEventReader`
* Added new core functions, including `relative_date`
* Added Amazon account linking scopes
* Reworked Amazon value catalogs
* Added some missing preview templates
* Fixed everything resolving to strings in expression evaluation
* Fixed `RunOnce` element not setting its `triggered` state properly
* Fixed `ConversationBlock` not resetting its `failure_count` properly
* Fixed Dialogflow response for lists
* Fixed get option in Dialogflow request
* Added reset loop parameter in LoopBlock
* Added loop property to element randomizer
* Added support for Alexa Permissions

## 0.22.7.0 - 2021-11-17
* Apply service migration for default alexa skill icons feature
* Update documentation of `IURLSupplier`
* Add new function relative_date
* Refactor `GetAmazonUserElement` to make api calls in api class
* Catalogs now split into two interfaces
* Rework how catalog versions are stored

## 0.22.6.0 - 2021-10-20
* Add support for Alexa In-Skill Purchases
* Add support for new property editor type
* Fix Amazon propagation

## 0.22.5.1 - 2021-09-23
* Add `explode` function
* Add the ability to use `#{}` syntax for evaluation in Generic APL Element
* Loop Block now properly shows help
* New custom gzip encoding middleware

## 0.22.5 - 2021-08-26
* Added User Scope to Request Scope
* Added support for Alexa APL
* Add `convo_val` function to core

### 0.22.4.1 - 2021-08-02
* Add param evaluation to components to support raw input
* Fix content type evaluation in `BodyParserMiddleware`

### 0.22.4 - 2021-07-28
* Slight tweak to create service from existing method

### 0.22.3 - 2021-07-19
* Add endpoint for Alexa Skill Auto Enablement
* Add ability to create service from existing export
* Remove various exceptions to make workflow less disrupting

### 0.22.2 - 2021-06-23
* fix is empty method in `AmazonCommandRequest`, `DefaultTextCommandRequest` and `ActionsCommandRequest.php`

### 0.22.1 - 2021-06-07

* Add `array_push` to custom functions
* Overhaul expression parsing regex
* Release mapping additional check
* Fixes for `LoopElement`
  * Fix `last` param
  * Evaluate `offset` and `limit`
* Removed `keys` function
 
### 0.22.0 - 2021-05-14

* Added Start Video Playback element
* Use Symfony 5.2
* `isSessionStart()` added to request interface
* Smaller fixes and improvements

### 0.21.2 - 2021-04-01

* default Amazon skill images fix

### 0.21.1 - 2021-03-29

* Removed content-length from comonent help handler

### 0.21.0 - 2021-03-27

* Added List Title and List Item as separate elements in `convo-visuals` package
* Enhanced preview for visual elements
* Old list element is marked as deprecated
* IMediaSourceContext refactored, added 2 new media playing elements
* PHP functions added: stripos, strtotime, time
* Allow propagation on Alexa Distribution Information from Convoworks
* Do not allow propagation of custom intents without sample utterances to Amazon Alexa
* Add the ability to generate System Relevant URLs for the Editor
* When transferring ownership of private service, add the previous owner to service Admins
* Add New Element in Alexa Package Init Amazon User 
* Minor fixes

### 0.20.0 - 2021-02-22

* Reworked data format returned from `ServiceTestHandler`
    * Now includes component parameters as well

* Added `ArrayUtil::arrayFilterRecursive`

* Workflow containers now implement `getAllChildren()`

* Optional property for block container params, `_separate`
    * If true, a separator will be rendered after the container in the editor

### 0.19.2 - 2021-02-19

* stringify value before `md5`
* added missing parent constructor calls in if elements

### 0.19.1 - 2021-02-13

* use `md5` to normalize service params keys

### 0.19.0 - 2021-02-09

* Overhauled preview generation
    * No longer done in `PreviewBuilder::read()`, each block now creates its own preview
    
* add `phpseclib/bcmath_compat` in `composer.json` to fix issue with missing `bccomp` function from `bcmath` module

* fix service deletion

* added function `substr` to core package

* added `ordinal` system entity

* fixed `DefaultFilterResult::isSlotEmpty()` to check empty string too (was `isset` only)

* added abilty to set predefined values for NOP filter

### 0.18.6 - 2021-01-08

* new dialogflow api
* removed unused dependencies from core package definition

### 0.18.5 - 2021-01-08

* Two more functions made available in expression language
* Remove faulty log statement in `IfElement` that assumed `$then_result` would always be a simple string
* Added `arrayDiffRecursive` function to `ArrayUtil`

### 0.18.4 - 2021-01-08

* Fixed method `isMediaRequest` in `AmazonCommandReqeust` - [#14](https://github.com/zef-dev/convoworks-core/issues/14)

### 0.18.3 - 2020-12-23

* Corrected `DialogflowPublisher` parent class arguments


### 0.18.2 - 2020-12-21

* Convo request is now optional in evaluateString() process - #11

### 0.18.0 - 2020-12-05

* Initial release & source import

