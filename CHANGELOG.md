# CHANGELOG

## [Current development]

## [Releases]

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

