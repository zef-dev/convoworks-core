# CHANGELOG

## [Current development]


## [Releases]

## 0.22.29 - 2024-10-16

* Registered PHP `constant()` function.
* Minor fixes

## 0.22.28 - 2023-11-24

* Registered PHP `array_reverse()` function.
* Log element now accepts log level and has ability to dump response in the test view

## 0.22.27.4 - 2023-10-30

* Registered `call_user_func_array()` as a custom function.
* Fixed calls to `call_user_func()` that had no arguments. 

## 0.22.27.3 - 2023-10-23

* Force no body for GET requests in the Http Query Element
* Fix detection of indexed arrays in the `call_user_func()`
* Registered PHP functions in the core package: `is_string()`, `is_float()`, `is_long()`, `is_countable()`, `is_null()`.

## 0.22.27.2 - 2023-09-08

* Corrected default values in function definitions.
* Fixed complex array key settings.
* Implemented a check for the existence of required packages during service import.

## 0.22.27.1 - 2023-08-29

* Registered PHP `function_exists()` function.

## 0.22.27 - 2023-08-29

* Registered PHP functions in the core package: `rawurlencode()`, `base64_encode()`, `hash_hmac()`, `uniqid()`, `http_build_query()`.
* Registered `call_user_func()` as a custom function. The difference is that arguments are passed as a single array (not spread).
* Added `parse_csv_file()` custom function, which internally uses `fgetcsv()`.

## 0.22.26 - 2023-08-11

* Allow fragments to include themselves.
* Register PHP functions in the core package: `preg_replace()`, `array_diff()`, `htmlentities()`, `htmlspecialchars()`, `html_entity_decode()`.
* Register custom function in the core package: `html_to_markdown()`.
* Allow HTML in text message responses.


## 0.22.25 - 2023-07-21

* Add round() and number_format() functions
* Use json encode and decode functions directly
* Add failback flow to the special role processor

## 0.22.24 - 2023-07-18

* Add support for additional internal platforms
* Register is_object() and array_keys() PHP functions

## 0.22.23.1 - 2023-07-03

* Fix args (assoc array) passed as string to be evaluated
* Check if field exists when parsing complex keys

## 0.22.23.0 - 2023-06-06

* Add unlink() and set_time_limit() PHP function
* Add parse_url() PHP function
* Add array_slice() and array_chunk() PHP functions
* Do not load definitions (templates, intents) until required

## 0.22.22.0 - 2023-05-15

* Fix missing default failback handling
* Add $_POST, $_GET and othe server variables to the evaluation context 

## 0.22.21.0 - 2023-05-03

* Fix setting deep values with expression language
* Add unregister platform endpoint
* Add IRestPlatform
* Add filter_var() and print_r() PHP functions
* Add timezone aware request interface
* Use separate convo chat request

## 0.22.20.0 - 2023-05-03

* Fix web chat request parameters

## 0.22.19.1 - 2023-04-14

* Test chat now can reset session
* Few preview related changes

## 0.22.19.0 - 2023-04-06

* Added file contents and json encoding PHP functions
* Added findAncestor() to the service components interface

## 0.22.18.0 - 2023-01-16

* Added `postalAddress` system entity
* Fix text filters to accept 0 and allow them to evaluate search string
* Correct http query element body evaluation

## 0.22.17.1 - 2022-12-29

* Sanitize missed index in the SeekAudioPlaybackBySearch

## 0.22.17.0 - 2022-12-27

* Fixed type safety in evaluation context
* Added ability to parse (modify) slot values with EntityModel
* Do not activate filter if slot is empty - ""
* Support for the External platforms through packages
* Delegate session start calls to default state (first) if elements or processors are empty
* Added support for Dialogflow ES

## 0.22.16.0 - 2022-11-04
* Added support for Alexa location services
* Several improvements in audio player
* Speed up package access
* Added event dispatcher and conversation request event
* Added support for Radio station streaming

## 0.22.15.0 - 2022-07-29
* Added new block type - Voice PIN Confirmation block
* Added support for Alexa dialogue delegation
* Fixed collection skipping optional elements
* Add optional element interface to the run once element
* Consider value "?" as empty value in Convo Intent Reader

## 0.22.14.0 - 2022-06-10
* Add functions `array_filter`, `serialize`, `unserialize`, `empty`
* Fix help file loading
* Cache variables so that they don't have to be constantly re-evaluated
* Improvements to logging
* Take slots into consideration when checking `disabled` in `ConvoIntentReader`

## 0.22.13.0 - 2022-05-05
* Add new element - Element Generator
  * Iterates over a given collection and for each item, creates an instance of its child element
* Add `implode()` to core functions
* Add ability to remove a child
* Expand list of allowed characters in Amazon Alexa utterances
* Add new element - Simple Card
* Add ability to fast forward and rewind audio playback
* Add ability to seek playback via search
* Get access token from person object in Alexa Request if available
* Add support for Person Profile API in `GetAmazonCustomerProfileElement`
* Add `AmazonCommandRequest::getPersonId`

## 0.22.12.2 - 2022-03-30
* Change default value for `ConvoIntentReader::required_slots` from empty string `""` to empty array `[]`

## 0.22.12.1 - 2022-03-25
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

