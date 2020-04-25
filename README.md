Reefine - ExpressionEngine addon
================================

Reefine is an ExpressionEngine add-on that allows the user to search, filter and refine channel entries (eg products) on your site. It is aimed at ecommerce sites but can be used for sorting any kind of data stored in channel entries.

*   [Download for EE5](https://github.com/Patrick64/reefine/archive/master.zip)
*   [Download for previous versions of EE](https://github.com/Patrick64/reefine/archive/all-versions.zip)
*   [About the developer](http://patrickw.tech/)


  

### Table of Contents
<!-- run ~/opt/gh-md-toc --insert README.md to update -->
<!--ts-->
   * [Reefine - ExpressionEngine addon](#reefine---expressionengine-addon)
         * [Table of Contents](#table-of-contents)
      * [Documentation Versions](#documentation-versions)
      * [Installation](#installation)
         * [Install Reefine 1.x (ExpressionEngine 2)](#install-reefine-1x-expressionengine-2)
         * [Install Reefine 2.x or 3.x (ExpressionEngine 3, 4 &amp; 5)](#install-reefine-2x-or-3x-expressionengine-3-4--5)
      * [Hello world](#hello-world)
      * [Parameters](#parameters)
         * [parse](#parse)
         * [channel](#channel)
         * [url](#url)
         * [url_output](#url_output)
         * [theme](#theme)
         * [method](#method)
         * [author_id](#author_id)
         * [disable_search](#disable_search)
   * [{title}](#title)
         * [fix_pagination](#fix_pagination)
         * [site](#site)
         * [search:<em>field name</em>](#searchfield-name)
         * [category](#category)
         * [category_url](#category_url)
         * [fixed_order](#fixed_order)
         * [show_expired](#show_expired)
         * [show_future_entries](#show_future_entries)
         * [status](#status)
         * [filter:fields](#filterfields)
         * [filter:<em>filter group name</em>](#filterfilter-group-name)
         * [filter:<em>filter group</em>:type](#filterfilter-grouptype)
            * [filter:<em>filter group</em>:type="list"](#filterfilter-grouptypelist)
            * [filter:<em>filter group</em>:type="search"](#filterfilter-grouptypesearch)
            * [filter:<em>filter group</em>:type="number_range"](#filterfilter-grouptypenumber_range)
            * [filter:<em>filter group</em>:type="month_list"](#filterfilter-grouptypemonth_list)
            * [filter:<em>filter group</em>:type="tree"](#filterfilter-grouptypetree)
         * [filter:<em>filter group</em>:fields](#filterfilter-groupfields)
         * [filter:<em>filter group</em>:label](#filterfilter-grouplabel)
         * [filter:<em>filter group</em>:show_empty_filters](#filterfilter-groupshow_empty_filters)
         * [filter:<em>filter group</em>:custom_titles](#filterfilter-groupcustom_titles)
         * [filter:<em>filter group</em>:custom_values](#filterfilter-groupcustom_values)
         * [filter:<em>filter group</em>:default](#filterfilter-groupdefault)
         * [filter:<em>filter group</em>:delimiter](#filterfilter-groupdelimiter)
         * [filter:_filter group:_join](#filter_filter-group_join)
         * [filter:_filter group:_orderby](#filter_filter-group_orderby)
         * [filter:_filter group:_sort](#filter_filter-group_sort)
         * [filter:<em>filter group</em>:category_group](#filterfilter-groupcategory_group)
         * [filter:<em>filter group</em>:show_separate_only](#filterfilter-groupshow_separate_only)
      * [Single Variables](#single-variables)
         * [{total_active_filters}](#total_active_filters)
         * [{total_entries}](#total_entries)
         * [{querystring}](#querystring)
      * [{entries} Variable Pair](#entries-variable-pair)
         * [{entries} » {entry_ids}](#entries--entry_ids)
         * [{entries} » {total_entries}](#entries--total_entries)
      * [{list_groups} Variable Pair](#list_groups-variable-pair)
         * [{label}](#label)
         * [{list_groups} » {group_name}](#list_groups--group_name)
         * [{list_groups} » {label}](#list_groups--label)
         * [{list_groups} » {type}](#list_groups--type)
         * [{list_groups} » {total_filters}](#list_groups--total_filters)
         * [{list_groups} » {active_filters}](#list_groups--active_filters)
         * [{list_groups} » {matching_filters}](#list_groups--matching_filters)
         * [{list_groups} » {clear_url}](#list_groups--clear_url)
      * [{list_groups} » {filters} Variable Pair](#list_groups--filters-variable-pair)
         * [{list_groups} » {filters} » {filter_id}](#list_groups--filters--filter_id)
         * [{list_groups} » {filters} » {filter_value}](#list_groups--filters--filter_value)
         * [{list_groups} » {filters} » {filter_title}](#list_groups--filters--filter_title)
         * [{list_groups} » {filters} » {url}](#list_groups--filters--url)
         * [{list_groups} » {filters} » {filter_quantity}](#list_groups--filters--filter_quantity)
         * [{list_groups} » {filters} » {filter_active}](#list_groups--filters--filter_active)
         * [{list_groups} » {filters} » {filter_active_class}](#list_groups--filters--filter_active_class)
         * [{list_groups} » {filters} » {filter_active_boolean}](#list_groups--filters--filter_active_boolean)
      * [{tree_groups} Variable Pair](#tree_groups-variable-pair)
         * [{tree_groups} » {filters}](#tree_groups--filters)
         * [{tree_groups} » {filters} » {has_active_subfilters}](#tree_groups--filters--has_active_subfilters)
         * [{tree_groups} » {filters} » {has_active_subfilters_class}](#tree_groups--filters--has_active_subfilters_class)
         * [{tree_groups} » {filters} » {subfilters_1}](#tree_groups--filters--subfilters_1)
         * [{tree_groups} » {filters} » {subfilters_1} » {subfilters_2}](#tree_groups--filters--subfilters_1--subfilters_2)
         * [{tree_groups} » {filters} » {subfilters_1} » {subfilters_2} » {subfilters_3} ...](#tree_groups--filters--subfilters_1--subfilters_2--subfilters_3-)
      * [{number_range_groups} Variable Pair](#number_range_groups-variable-pair)
         * [{label}](#label-1)
         * [{number_range_groups} » {filters} » {filter_min}](#number_range_groups--filters--filter_min)
         * [{number_range_groups} » {filters} » {filter_max}](#number_range_groups--filters--filter_max)
         * [{number_range_groups} » {filters} » {filter_min_value}](#number_range_groups--filters--filter_min_value)
         * [{number_range_groups} » {filters} » {filter_max_value}](#number_range_groups--filters--filter_max_value)
      * [{search_groups} Variable Pair](#search_groups-variable-pair)
      * [{active_groups} Variable Pair](#active_groups-variable-pair)
         * [Selected Criteria](#selected-criteria)
      * [{filter_groups} Variable Pair](#filter_groups-variable-pair)
      * [Change Log](#change-log)
         * [Version 3.2.0](#version-320)
         * [Version 3.1.1](#version-311)
         * [Version 3.0.0](#version-300)
         * [Version 2.1.2](#version-212)
         * [Version 2.1](#version-21)
         * [Version 2.0](#version-20)
         * [Version 1.6](#version-16)
         * [Version 1.5](#version-15)
         * [Version 1.4](#version-14)
         * [Version 1.3](#version-13)
         * [Version 1.2](#version-12)
         * [Version 1.1](#version-11)
         * [Version 1.0](#version-10)
      * [Compatibility](#compatibility)
         * [Categories](#categories)
         * [Selects / Checkboxes / Multiselects](#selects--checkboxes--multiselects)
         * [Structure](#structure)
         * [Expresso Store](#expresso-store)
         * [Relationship &amp; Playa fieldtypes](#relationship--playa-fieldtypes)
         * [Grid &amp; Matrix fieldtypes](#grid--matrix-fieldtypes)
         * [Date fieldtype](#date-fieldtype)
         * [Other Custom Fieldtypes](#other-custom-fieldtypes)
      * [About Reefine](#about-reefine)
         * [License](#license)
         * [Support](#support)
      * [Tips and Tricks](#tips-and-tricks)
         * [SEO](#seo)
         * [Performance](#performance)
         * [Themes and customisation](#themes-and-customisation)
         * [Seperate filter groups](#seperate-filter-groups)
         * [Colour](#colour)
         * [Sorting entries](#sorting-entries)
      * [Known Issues](#known-issues)
         * [Reefine not processing some tags in filters](#reefine-not-processing-some-tags-in-filters)
      * [Code Samples](#code-samples)
         * [Complete example using separate filters](#complete-example-using-separate-filters)
         * [Complete example using flexible filter groups](#complete-example-using-flexible-filter-groups)
         * [Example with form and month_list](#example-with-form-and-month_list)

<!-- Added by: patrick, at: Sat 25 Apr 14:42:51 BST 2020 -->

<!--te-->

Documentation Versions
----------------------

*   [Document for previous versions of Reefine](http://www.ralphmedia.co.uk/docs/reefine/)

Installation
------------

Make sure your system meets the minimum requirements:

*   [ExpressionEngine](http://expressionengine.com/) 2.4.0 or later
*   PHP 5

First download and extract the Reefine ZIP file. There are folders, ee2, ee3, ee4 and ee5.

### Install Reefine 1.x (ExpressionEngine 2)

1.  Download and extract the ee2 folder in the Reefine ZIP file.
2.  Upload the third\_party/reefine folder to system/expressionengine/third\_party/
3.  Upload the themes/third\_party/reefine folder to themes/third\_party/
4.  Install the module in Add-Ons → Modules
5.  Check the ee2/docs folder for more instructions on Reefine 1.x

### Install Reefine 2.x or 3.x (ExpressionEngine 3, 4 & 5)

1.  Download and extract the Reefine ZIP file. Go into the ee3/ee4/ee5 folder depending on your ExpressionEngine version
2.  Upload the /system/user/addons/reefine folder to /system/user/addons/reefine
3.  Upload the /themes/user/reefine folder to /themes/user/reefine
4.  Click Developer → Add-on Manager. Scroll down to "Third Party Add-Ons" and click install next to Reefine.

Hello world
-----------

This is a small tutorial to create a clothing shop filter. Please refer to the [Ralph](http://www.ralphmedia.co.uk/reefine/demo_basic) website for a live demo.

1.  Create a Channel called "clothes"
2.  Create a Field group called "clothes"
3.  Add a field with short name "size" with the type "select dropdown" and add values Small, Medium, and Large.
4.  Add three more fields with short names "product\_type", "colour" and "price" with the type "text"
5.  Add another field with short name "product\_image" with type "file".
6.  Associate the field group with the channel you created.
7.  Add some entries to your channel, ensure that you add an image to product\_image and the price field contains just a number, the other fields can be anything you want.
8.  Create a new template in a template group. (eg shop/search )
9.  Paste following code into template ([Github Gist](https://gist.github.com/Patrick64/7892949#file-reefine-hello-world-html)). Then go to your template and see the results.

Parameters
----------

### parse

This must be set to **inward** for the {entries} tag works correctly.

`parse="inward"`

**Important:** This is required for Reefine to work properly

### channel

Specify a list of channels to search through, seperated by pipe |.

`channel="food|clothes|cutlery"`

### url

This specifies the structure of your url. It must show where each **filter group** goes in the url. The filter group is specified in curly braces. Each filter group must be seperated by characters that are unlikely to appear in the filter.

This example will produce an url of mypage followed by a search for title and then colour. For example if the user filters by title **mug**s and colour **red** it will produce **/mypage/mugs/red**

`fields="title|colour"  url="mypage/{title}/{colour}"`

If the user does not specify a title it will replace it with the text "any". You can override this behaviour by specifying the **any text** after a pipe. For example this will produce **mypage/any-title/any-colour** if no filters are selected.

`url="mypage/{title|any-title}/{colour|any-colour}"`

If the user searches by multiple filters for a filter group you can also specify the text that will join them. By default it is "-or-" (for join="or" filter groups), or "-and-" (for join="and" filter groups). This applies to filter groups of type **text** only. For example this will product **mypage/mugs/red-otherwise-green/** if the user filters by title **mugs** and colour **red** or **green**.

`url="mypage/{title|any-title}/{colour|any-colour|-otherwise-}"`

For filters of the type number\_range the url by default is "any" for no filter, "-to-" as a seperator between the range of values, "at-least-" if only the min value is specified and "at-most-" if only the max value is specified. You can override these using these pipe seperated values:

`url="mypage/{title}/{colour}/{price|any|-to-|at-least-|at-most-}"`

You don't need to seperate filter groups by a slash you can use any text you want. This will produce urls such as **buy-some-delightful-things-today** or **buy-some-green-mugs-today**

`url="buy-some-{colour|delightful}-{title|things}-today"`

**Important:**  If you are using method="url" (default) then this is required and all filter groups must be specified in the url.

### url\_output

Default: same as "url" parameter

Use this if you need to specify a different url for output to links. This is useful if you want to link to a new page on filtering or if you're using a language indicator in the first segment that isn't sent through to expressionengine. Reefine will still use the "url" parameter to get the current filter values.

`url="/{colour}/{price}"  
url_output="/shop/{colour}/{price}"  
`

### theme

You can specify a theme to use which will output the filters selectors to save you having to create them yourself. Themes are found in the /themes/third\_party/reefine folder. Reefine comes with the themes "shop" and "form". The "form" theme is compatible with all [methods](#method) whereas the "shop" theme is compatible with all methods except for method="post".

`theme="shop"`

You can create your own themes by copying the theme from the /themes/third\_party/reefine folder, and then reference your new theme using the directory name. For example /themes/third\_party/reefine/mytheme would be:

`theme="mytheme"`

The theme parameter is optional - you can specify all html & css in the template itself. See the [code samples](#Code-Samples) for an idea of how this works.

### method

Default: url

This is the method by which the user can interact with Reefine. The supported methods are **url**, **ajax**, **get** and **post**. The method must be supported by your theme / template code. The existing themes "shop" and "form" will read the method used and adapt accordingly. If you are using your own theme or template code you should note the following:

*   `method="url"` (default) allows the user to filter by using links. You must provide the [url](#url) parameter for this to be used. You can use [links with filter url](#-list_groups-filters-url-) or a form. If you are using a form it will automatically redirect to the correct URL when the form is submitted, you will also need to add `<input type="hidden" name="form\_post" value="yes"/>` which fixes a problem where the user can submit a form with no filters selected.
*   `method="ajax"` will support returning Reefine as an AJAX request. You can use a form or [filter the url](#-list_groups-filters-url-) links. If you add ajax\_request=1 to the get/post request it will return just the form on it's own without the rest of the template. If you are using just template code to or making your own theme from scratch you will need to use javascript / jQuery to handle the AJAX request. Refer to themes/third\_party/reefine/shop/ajax.js or themes/third\_party/reefine/form/ajax.js for examples.
*   `method="get"` will allow for submitting the request via the GET method (so it appears in the URL eg http://www.example.com/products?colour=red ). You can use a form <form method="get" ...> or by using [filter the url](#-list_groups-filters-url-) links.
*   `method="post"` will allow for submitting the request via POST eg <form methd="POST" ... >. Unless you have disabled secure forms you will need to add <input type="hidden" name="XID" value="{XID\_HASH}" /> to your form as well. This method does not support [filter the url](#-list_groups-filters-url-) links, you will need to use a form.

### author\_id

Pipe separate list of author IDs. This parameter can restrict the entries shown to just these author IDs.

`author_id="1|3"`

### disable\_search

Default: no

You can add the parameter disable\_search="yes" which will make Reefine ignore any filter values in the URL or post. This is useful for showing all filter options on a subpage. For example if you are on the product's page (http://www.example.com/products/items/red-trousers) you don't want Reefine to pick up the segments "items/red-trousers" so you add disable\_search="yes" to ignore them:

`<nav> {exp:reefine channel="clothes" parse="inward" theme="shop" disable_search="yes"  
filter:fields="title|product_type|size|colour|price" filter:price:type="number_range"  
url="/products/{product_type}/{size}/{colour}/{price}/{title}"}  
{/exp:reefine} </nav>  
  
<article>{exp:channel:entries channel="clothes"} <h1>{title}</h1> <p>{content}</p> {/exp:channel:entries}</article>`

### fix\_pagination

Default: no

If you're using Structure and/or freebie you may find the pagination doesn't work correctly. Set this to yes to try and fix this.

`fix_pagination="yes"`

### site

Specify the site ID if using Multi site manager.

`site="1"`

### search:_field name_

You can search by a particular field value or the title. This works in the same way as the channel:entries tag. Please refer to the documentation on [search:field\_name for channel entries](http://expressionengine.com/user_guide/modules/channel/channel_entries.html#search-field-name) in the ExpressionEngine documentation. Additionally you can use this to search the title with search:title="abc".

`search:colour="red"`

### category

This parameter will limit all your entries to a particular category by it's category ID. You can include multiple categories seperated by pipe "|".

`category="2|3"`

You can also exclude categories using not:

`category="not 2|3"`

### category\_url

This parameter will limit all your entries to a particular category. This uses the category url title.

`category_url="{segment_2}"`

### fixed\_order

Pipe separated lists of entry ids that will allow you specify the exact order of the entry\_ids go in. You will need to put this in the fixed\_order for {exp:channel:entries} for example:

`{exp:reefine **fixed_order="1|2|3|4"** ...snip...}  
{entries}  
{exp:channel:entries entry_id="{entry_ids}" **fixed_order="{entry_ids}"** dynamic="no" } ... snip ... {/exp:channel:entries}  
{/entries}  
{/exp:reefine}`

### show\_expired

Default: no

Show expired entries. You will also need to add this to your {exp:channel:entries} tag.

`show_expired="yes"`

### show\_future\_entries

Default: no

Show entries with an entry date that is in the future. You will also need to add this to your {exp:channel:entries} tag.

`show_future_entries="yes"`

### status

Default: open

This will limit the entries by their status. Multiple statuses are seperated by a pipe character "|".

`status="featured|on_offer"`

**Important:** The containing exp:channel:entries tag will also need to have the same status tag, otherwise the filter and search results will differ.

### filter:fields

Pipe | seperated list of fields to filter by. For each field it will assume some defaults, such as specifying "title" will assume type is "search", a checkboxes/multiselect field will assume delimiter="|". You can then change some of attributes of the filter by referencing **filter:_field name_:_..._** as below. Each field will then be a filter group.

`filter:fields="title|product_type|colour"`

### filter:_filter group name_

You can specify extra information about filter groups by using a parameter in the format **filter:**_**filter group name**_**:_variable_**. Some examples:

`filter:search:fields="title|product_type|colour"  
filter:search:category_group="3"  
filter:search:type="search"`

`filter:price:fields="price"  
filter:price:type="number_range"`

`filter:colour:fields="colour"  
filter:colour:delimiter="|"  
filter:colour:label="Paint Colour"  
filter:colour:join="or"  
filter:colour:orderby="active"  
`  

### filter:_filter group_:type

Default: list

Specify the different filter group types which enables different features of Reefine. For example:`filter:price:type="number_range"  
filter:search:type="search"  
filter:colour:type="list"`

**Note:**  Not related to the field types in ExpressionEngine.

#### filter:_filter group_:type="list"

This is the default method. It will show a list of all possible value of the fields specified with the quantity. Also see [{list\_groups}](#-list_groups-Variable-Pair)

#### filter:_filter group_:type="search"

Search is for text searches with a text box. It will search each word individually. Also see [{search\_groups}](#-search_groups-Variable-Pair)

`filter:search:fields="title|product_type|colour"  
filter:search:type="search"  
`

#### filter:_filter group_:type="number\_range"

The number range allows the user to specify a minimum and maximum value to filter by. This is useful for price ranges. It will output extra information in the filter variables pair and the tag [{number\_range\_groups}](#-number_range_groups-Variable-Pair)

`filter:price:fields="price"  
filter:price:type="number_range"  
`

#### filter:_filter group_:type="month\_list"

This type will allow the user to filter the entries by a list of months. This example if for a channel with the Date fields event\_from (the date the event starts) and event\_to (the date the event ends, this is useful in case the event covers more than one month). Also notice the filter:month:where\_after parameter will hide all months that have passed. You can also use where\_before to hide months in the future. The fields entry\_date and expiration\_date are also supported.

`filter:month:type="month_list"  
filter:month:fields="event_from|event_to"  
filter:month:where_after="{current_time}"  
`

#### filter:_filter group_:type="tree"

This type will show categories with subcategories in a tree format, much like how ExpressionEngine shows them in the control panel. Also see [{tree\_groups}](#-tree_groups-Variable-Pair)

`filter:genre:category_group="2"  
filter:genre:type="tree"  
`

### filter:_filter group_:fields

Pipe | seperated list of fields to filter by. Also includes title, entry\_date, expiration\_date and status.

`filter:search:fields="title|product_type|colour"`

**Important:**  If one of the fields is of type checkboxes or multiselect list you must specify **filter:_filter group_:delimiter="|"**

### filter:_filter group_:label

The label for the filter group.

`filter:colour:label="Paint Colour"`

### filter:_filter group_:show\_empty\_filters

Whether to output filters that have no matches in the current search. Value can be yes or no, default is "no".

`filter:colour:show_empty_filters="yes"`

### filter:_filter group_:custom\_titles

### filter:_filter group_:custom\_values

For making a filter group with fixed values or adding some hard coded values to a filter group. This is usful for creating ways for the user to change orderby or number of pages. You can then use {active\_filter\_values} to access the selected value. Text is pipe separated and each item in custom\_values has the repsective title in custom\_titles, i.e. the first item in custom\_values (eg title) has the title of the first item in custom\_titles (eg Product Name) and so on.

`filter:orderby:custom_titles="Product Name|The Price"  
filter:orderby:custom_values="title|price"  
filter:orderby:default="price"  
filter:orderby:show_separate_only="yes"  
filter:orderby:join="none"`

### filter:_filter group_:default

Default selected value for this filter group. If using categories this would be the category short name.

`filter:postage:default="first_class"`

### filter:_filter group_:delimiter

This is the delimiter to use for fields that contain more than one value. By default ExpressionEngine uses the pipe character |. You should specify **filter:_filter group_:delimiter="|"** when the filter group contains a field that is a checkboxes or multiselect.

`filter:store:delimiter="|"`

### filter:_filter group:_join

Possible options are:

*   or (default)
*   and
*   none

This allows you specify how multiple filters are treated. If the join is **or** reefine will show an entry if it matches at least one filter in the filter group. If the join is **and** reefine will show an entry if it matches all filters in the filter group. If the join is **none** reefine will only allow the user to select one filter for that filter group.

`filter:colour:join="or"`

### filter:_filter group:_orderby

This specifies the order that the filters are displayed in the filter group. Possible values are:

*   value (default, order alphabetically by the text value of the filter)
*   quantity (order by the quantity of matching entries for filter, most to least)
*   active (put active filters at top and order the rest alphabetically)
*   active\_quantity (put active filters at top and order the rest by quantity)

You can also specify a pipe "|" seperated list of filter values to set your own custom order.

`filter:colour:orderby="active_quantity"  
filter:city:orderby="london|bristol|cheltenham|plymouth"`

### filter:_filter group:_sort

Default: asc

This specifies the direction of order that the filters are displayed in the filter group. It can be "asc" for ascending or "desc" for decending.

`filter:colour:sort="desc"`

### filter:_filter group_:category\_group

Filter by categories by specifying a category group. This is a pipe | seperated list of category IDs. In the same way the fields parameters works you can specify a list of categories. For example if I have a category group for departments with the ID 2 I can filter by the departments.

`filter:department:category_group="2"`

### filter:_filter group_:show\_separate\_only

Default: no

Exclude this filter from the showing up in the tags for flexible filter groups, that combine filter groups (i.e. {list\_groups}, {number\_range\_groups}, {search\_groups}, {active\_groups} and {filter\_groups}). To show this filter you would need to specify it specifically, see [Complete example using separate filters](#Complete-example-using-separate-filters).

`filter:department:show_separate_only="yes"`

Single Variables
----------------

### {total\_active\_filters}

Number of active filters across all filter groups.

`{if total_active_filters > 0}You have selected a filter{/if}`

### {total\_entries}

Total number of matching entries

`{if total_entries == 0} No entries found. {/if}`

### {querystring}

Query string of current search, useful for pagination if you're using [method="get"](#method) as ExpressionEngine does not add the querystring onto {auto\_path} in pagination.

`{paginate}  
{if next_page}  
   <a href="{auto_path}?{querystring}">Next page</a>  
{/if}  
{/paginate}`

{entries} Variable Pair
-----------------------

Where the magic happens. This variable pair will contain your channel entries code to display the results

### {entries} » {entry\_ids}

A pipe | seperated list of entry IDs that match the current filters. You can use this in a channel entries tag to output your results. If no entries are found {entry\_ids} will be -1.

`{exp:channel:entries entry_id="{entry_ids}" dynamic="no" orderby="title" status="not closed" limit="10" paginate="yes"}  
    <frameset>  
    <img src="{product_image}" alt="{title}"/><br />  
    <a href="/products/{url_title}">{title}</a><br />  
    <strong>&pound;{price}</strong>  
    </frameset>  
    {paginate}<p class="paging">Page {current_page} of {total_pages} pages {pagination_links}</p>{/paginate}  
  {/exp:channel:entries}`

### {entries} » {total\_entries}

Total number of matching entries

`{if total_entries == 0} No entries found. {/if}`

{list\_groups} Variable Pair
----------------------------

This variable pair will contain your list of filters for all the filter groups of type "list". Here is an example from the "shop" theme:

`{list_groups}  
<h3 class="group_{group_name}">{label}</h3>  
<ul>  
{filters}  
<li class="{filter_active_class}" aria-selected="{filter_active_boolean}">  
<a href="{url}">{filter_title} ({filter_quantity})</a>  
</li>  
{/filters}  
</ul>  
{/list_groups}  
`

### {list\_groups} » {group\_name}

The filter group name that is derived from **filter:_group name_** tag or the EE field name. Can be used for css classes.

`{list_groups}<div class="filter_{group_name}">...</div>{/list_groups}`

### {list\_groups} » {label}

Filter group label to display to user.

`{list_groups}<h2>{label}</h2>....{/list_groups}`

### {list\_groups} » {type}

Filter group type (list, number\_range, search)

`{list_groups}{if type=="number_range"}...{if:else}....{/if}{/list_groups}`

### {list\_groups} » {total\_filters}

Total number of possible filters in filter group

`{list_groups}{if total_filters > 0}...{/if}{/list_groups}`

### {list\_groups} » {active\_filters}

Total number of filters that have have been selected by the user.

`{list_groups}Filtering by {active_filters} filters.{/list_groups}`

### {list\_groups} » {matching\_filters}

Total number of filters that have at least one matching entry.

`{list_groups}{if matching_filters > 0}...{if:else}No filters match{/if}{/list_groups}`

### {list\_groups} » {clear\_url}

The url to remove all active filters from the filter group.

`{list_groups}<a href="{clear_url}">Clear</a>....{/list_groups}`

{list\_groups} » {filters} Variable Pair
----------------------------------------

This variable pair will contain your list of possible filter values for the filter group. The standard ExpressionEngine variables {count} and {total\_results} are also available.

### {list\_groups} » {filters} » {filter\_id}

For categories this will be the category ID. For all other field types this is blank.

`{list_groups}<ul>{filters}<li><a href="{url}">  
{exp:channel:categories id="**{filter_id}**"}{category_image}{/exp:channel:categories}  
</a></li>{/filters}</ul>{/list_groups}`

### {list\_groups} » {filters} » {filter\_value}

This is the possible filter value, which is used in the URL. For categories this will be the category url title. For relationship fields this will be the url\_title of the entry.

`{list_groups}<ul>{filters}<li><a href="{url}">**{filter_value}**</a></li>{/filters}</ul>{/list_groups}`

### {list\_groups} » {filters} » {filter\_title}

This is the possible filter title for displaying to the user.

`{list_groups}<ul>{filters}<li><a href="{url}">**{filter_title}**</a></li>{/filters}</ul>{/list_groups}`

### {list\_groups} » {filters} » {url}

The URL for that filter. No need to put {site\_url} before it as it should do this itself.

`{list_groups}<ul>{filters}<li><a href="**{url}**">{filter_title}</a></li>{/filters}</ul>{/list_groups}`

### {list\_groups} » {filters} » {filter\_quantity}

The number of matching entries for that filter.

`{list_groups}<ul>{filters}<li><a href="{url}">{filter_title} (**{filter_quantity}**)</a></li>{/filters}</ul>{/list_groups}`

### {list\_groups} » {filters} » {filter\_active}

True if the filter is active (ie has been selected by user)

`{list_groups}<ul>{filters}<li class="{if **filter_active**}active{if:else}inactive{/if}" >...</li>{/filters}</ul>{/list_groups}`

### {list\_groups} » {filters} » {filter\_active\_class}

If the filter is active this returns "active", if not "inactive". Useful for CSS class names.

`{list_groups}<ul>{filters}<li class="{filter_active_class}" >...</li>{/filters}</ul>{/list_groups}`

### {list\_groups} » {filters} » {filter\_active\_boolean}

If the filter is active this returns "true", if not "false". Useful for specifying aria-selected for screen reader users.

`{list_groups}<ul>{filters}<li aria-selected="{filter_active_boolean}">...</li>{/filters}</ul>{/list_groups}`

{tree\_groups} Variable Pair
----------------------------

This variable pair will contain all filter groups of the type "tree". It is used for showing categories with subcategories much like how ExpressionEngine shows them in the control panel. It contains all the same variables as the {list\_groups} pair Each item in the {filters}...{/filters} pair represents a top most category. Additionally the {filters}...{/filters} variable pair also contains the variable pair {subfilters\_1}...{/subfilters\_1} which represents a subcategory which in turn has the variable pair {subfilters\_2}...{/subfilters\_2} which represents a sub-sub-category and so on. If you are using the shop theme then this will be taken care of for you. Here is an example taken from the "shop" theme:

### {tree\_groups} » {filters}

This represents a main category and has all the same variables as [{list\_groups} > {filters}](#-list_groups-filters-Variable-Pair)

### {tree\_groups} » {filters} » {has\_active\_subfilters}

This is true if this current filter has at least one subfilter selected.

`{if has_active_subfilters}...{/if}`

### {tree\_groups} » {filters} » {has\_active\_subfilters\_class}

The same as {tree\_groups} » {filters} » {has\_active\_subfilters} but it outputs "has-active-subfilters" if true, and "no-active-subfilters" if false. This can be used for styling, for example if you want to hide subfilters until the user has selected the parent filter.

`<li class="{filter_active_class} {has_active_subfilters_class}">  
`

### {tree\_groups} » {filters} » {subfilters\_1}

This represents a sub-category. It has all the same variables as {tree\_groups} » {filters} BUT each variable is appended with the subfilter number. So {url} is {url\_1}, {filter\_title} is {filter\_title\_1} and so on.

### {tree\_groups} » {filters} » {subfilters\_1} » {subfilters\_2}

This represents a sub-sub-category. It has all the same variables as {tree\_groups} » {filters} but each variable is appended with \_2.

### {tree\_groups} » {filters} » {subfilters\_1} » {subfilters\_2} » {subfilters\_3} ...

This represents a sub-sub-sub-category. It has all the same variables as {tree\_groups} » {filters} but each variable is appended with \_3 and so on, you get the idea :)

{number\_range\_groups} Variable Pair
-------------------------------------

This variable pair will contain all filter groups of the type "list". The {number\_range\_groups} variable pair contains all the same variables as the {list\_groups} pair. It contains only one filter which has the minimum and maximum values the user has specified and the minimum and maximum values of the filter for the current search. Here is an example from the "shop" theme:

`{number_range_groups}  
<h3 class="group_{group_name}">{label}</h3>  
<form method="post" class="number_range" >  
<input type="hidden" name="XID" value="{XID_HASH}" />  
{filters}  
<input type="text" name="{group_name}_min" id="{group_name}_min" placeholder="{filter_min}"  
value="{filter_min_value}" aria-label="Minimum value"/>  
&mdash; <input type="text" name="{group_name}_max" id="{group_name}_max" placeholder="{filter_max}"  
value="{filter_max_value}" aria-label="Maximum value"/>  
{/filters}  
<input type="submit" name="submit" value="Go" />  
</form>  
{/number_range_groups}  
`

### {number\_range\_groups} » {filters} » {filter\_min}

The minimum possible value of the filter. Applies to filter groups of type **number\_range** only.

`{number_range_groups}{filters}<p>Min: {filter_min}</p>...{/filters}{/number_range_groups}`

### {number\_range\_groups} » {filters} » {filter\_max}

The maximum possible value of the filter. Applies to filter groups of type **number\_range** only.

`{number_range_groups}{filters}<p>Max: {filter_max}</p>...{/filters}{/number_range_groups}`

### {number\_range\_groups} » {filters} » {filter\_min\_value}

The minimum value to filter by as specified by the user. Applies to filter groups of type **number\_range** only.

`{number_range_groups}{filters}...<input name="{group_name}_min" value="{filter_min_value}" />...{/filters}{/number_range_groups}`

### {number\_range\_groups} » {filters} » {filter\_max\_value}

The maximum value to filter by as specified by the user. Applies to filter groups of type **number\_range** only.

`{number_range_groups}{filters}...<input name="{group_name}_max" value="{filter_max_value}" />...{/filters}{/number_range_groups}`

{search\_groups} Variable Pair
------------------------------

This variable pair will contain all filter groups of the type "search". It has all the same variables as {list\_groups}. It contains just the one filter which has the {filter\_title} variable. Here is an example from the default theme:

`{search_groups}  
{filters}  
<form method="post" class="{filter_active_class}">  
<input type="hidden" name="XID" value="{XID_HASH}" />  
<label for="{group_name}">{label}</label>  
<input  
type="text" id="{group_name}" name="{group_name}" placeholder="search"  
value="{filter_title}" title="Search ({filter_quantity})" />  
<input type="submit" name="submit" value="Go" />  
</form>  
{/filters}  
{/search_groups}  
`

{active\_groups} Variable Pair
------------------------------

This variable pair will contain all filter groups that have at least one active filter. This is useful for showing a list of selected criteria. It has all the same variables as {list\_groups}. It will only output active filter groups and active filters. Here is an example from the default theme:

`{if total_active_filters > 0}  
<div class="reefine_active_filters">  
<h3>Selected Criteria</h3>  
<ul>  
{active_groups}  
<li><strong><a class="remove-filter" href="{clear_url}">{label}:</a></strong></li>  
{filters}  
<li class="{filter_active_class}" aria-selected="{filter_active_boolean}">  
<a href="{url}">{filter_title}</a>  
</li>  
{/filters}  
{/active_groups}  
</ul>  
<p class="total_entries">{total_entries} items found</p>  
</div>  
{/if}  
`

{filter\_groups} Variable Pair
------------------------------

This variable pair will output all filters regardless of type. The variables available depend on what type the group is.

Change Log
----------

### Version 3.2.0

Release 2019-04-01

*   Added {querystring} tag
*   Big change to url encoding: Now uses --number-- encoding to fix disallowed character bug eg test@example becomes test--45--example
*   Improved category filter Performance
*   Bug fixes

### Version 3.1.1

Released 2019-01-07

*   Bug fixes
*   EE5 compatible

### Version 3.0.0

Release 3018-10-26

*   EE4 compatible

### Version 2.1.2

Released 2018-01-16

*   fixed bug with site id not in where clauses
*   EE3: added fix for ajax loading template override error

### Version 2.1

Release 2017-04-06

*   Bug fixes
*   Added author\_id, fixed\_order parameters
*   Added status as filter field

### Version 2.0

Release 2016-10-20

*   Made EE3 compatible.

### Version 1.6

Release 2015-09-28

*   Added new filter group type "tree" for showing categories and subcategories in a tree format.
*   Added fix\_pagination to {exp:reefine}
*   Added show\_separate\_only, default, custom\_values and custom\_titles parameters to filter groups.

### Version 1.5

Release 2015-01-05

*   Added url\_output, category, filter:filter\_group:sort, show\_future\_entries and show\_expired parameters.
*   status parameter can now be pipe seperated
*   Added active\_filters, matching\_filters and filter\_id tags
*   Added rel="nofollow" to links in "shop" theme
*   Fix for AJAX in "form" theme
*   Fix for special characters in URL.
*   Added custom ordering for filter:filter\_group:orderby parameter.

### Version 1.4

Release 2013-12-11

*   Added Grid, Matrix, Relationship & Playa compatibility
*   Added methods "get", "post", and "ajax"
*   Added "form" theme and made the "shop" theme compatible with get, post and ajax methods.
*   Added disable\_search parameter
*   Added month\_list filter group type.

### Version 1.3

Released 2013-09-25

*   Added Store fieldtype compatibility
*   Made seperate tags for each filter type and an active\_groups tag
*   Added filter:_filter group_:show\_empty\_filters parameter
*   Modified themes to be more portable
*   Made EE 2.7 compatible
*   Fixed bug with {count} tag
*   Modified default shop theme for better performance (by avoiding conditionals)
*   seperate\_filters parameter removed, reefine now automatically detects seperate filter tags.
*   Improved accessability of default theme for screen readers.

### Version 1.2

Release 2013-09-05

Bug Fixes

### Version 1.1

Released 2013-08-09

Breaking Changes

*   The {filter\_value} parameter is now used as the value for url title for categories. Please change {filter\_value} to {filter\_title} in your code.

Other changes

*   Added filter\_title field
*   Added fix for filter groups with multiple categories bug
*   Fixed number\_range problem with large numbers
*   Fix paging problem when there is no filter
*   Added category\_url parameter

### Version 1.0

First version of Reefine.

Compatibility
-------------

### Categories

Reefine can filter by categories, see [filter:filter group:category\_group](#filter-filter-group-category_group)

### Selects / Checkboxes / Multiselects

Reefine can filter by select dropdowns and multiselects. If you are using the [filter:fields](#filter-fields) parameter it will automatically pick this up. If you using [filter:field group:fields](#filter-filter-group-fields) you will have to add delimiter="|" see [delimiter parameter](#filter-filter-group-delimiter).

### Structure

Reefine is compatible with [Structure](http://buildwithstructure.com/). However you will need to add the [Freebie](http://devot-ee.com/add-ons/freebie) add-on so that the page will still display. You will need to add your page's segments to Freebie so Structure displays the correct page. Reefine will modify the page's URI string back to how it was before Freebie after processing to ensure EE's native paging still works.

### Expresso Store

Reefine is compatible with the [Expresso Store](http://devot-ee.com/add-ons/expresso-store) fieldtype. You can access the details of a product by specify the field name in the same way as in the template eg if your field of fieldtype Store is called "product" and you wanted to get the price you would use filter:_filter group_:fields="product:price". Please note it can only retrive the values from the **exp\_store\_products** database table so it will not be able to add tax or shipping. To filter by what's on sale use filter:_filter group_:fields="product:on\_sale".

### Relationship & Playa fieldtypes

Reefine is compatible with the Relationship fieldtype and [Pixel and Tonic's Playa fieldtype](http://devot-ee.com/add-ons/playa). Both work in the same way. Just enter the field name and the filter will show the related entry's title and use the url\_title for the url. For example:

`channel="films"  
filter:actors:fields="actors"  
url="/films/{actors}"  
`

You can also use a particular field within the related entry as the filter by appending a colon (:) and the field name of the related entry you want to filter. This example will show a filter for actor's nationality, so the user can see a list of all films that have French actors, for instance.

`channel="films"  
filter:actor_nationality:fields="actors:nationality"  
url="/films/{actor_nationality}"  
`

### Grid & Matrix fieldtypes

Reefine is compatible with the Grid fieldtype and [Pixel and Tonic's Matrix fieldtype](http://devot-ee.com/add-ons/matrix). Both will work in the same way. Just enter the field name of the Grid/Matrix field followed by a colon (:) and the field name within the grid/matrix. Please note that Reefine will only return the matching entries and will not be able to show filtered results of the grid rows. Here's an example of a clothes store that has clothes in the channel "clothes" and a Grid field "sizes" with columns "size" and "price". The price of an item of clothing is dependant on the size, the following example will allow the user to filter by both size and price:

`channel="clothes"  
filter:size:fields="sizes:size"  
filter:price:fields="sizes:price"  
filter:price:type="number_range"  
url="/store/{size}/{price}"  
`

### Date fieldtype

The date fieldtype is supported with [filter:filter group:type="month\_list"](#filter-filter-group-type-month_list-). You can also use the list and number\_range filter group types, however it will be output as a number (unix time) which you'll have to [convert to a date in the template](http://ellislab.com/expressionengine/user-guide/templates/date_variable_formatting.html) .

### Other Custom Fieldtypes

Reefine may not be able to filter by custom fieldtypes not listed above. Although you can still display the content of custom fieldtypes in the search results as Reefine just relies on the exp:channel:entries tag.

About Reefine
-------------

### License

A purchased license is required for each installation of the software. One (1) license grants the right to perform one (1) installation. Each additional installation of this software requires an additional purchased license.

[Read full license](../license.txt)

### Support

Support is available by email or the Devotee forum. We do not provide any guarantees on support but we will do our best if you are having problems.

*   var x="function f(x){var i,o=\\"\\",ol=x.length,l=ol;while(x.charCodeAt(l/13)!" + "=92){try{x+=x;l+=l;}catch(e){}}for(i=l-1;i>=0;i--){o+=x.charAt(i);}return o" + ".substr(0,ol);}f(\\")93,\\\\\\"310\\\\\\\\730\\\\\\\\520\\\\\\\\400\\\\\\\\610\\\\\\\\520\\\\\\\\K600\\\\" + "\\\\420\\\\\\\\OE@Pr\\\\\\\\UJCvli}kZjvxrqu-0M2S3h\`771\\\\\\\\c}(%Zmgfv/w520\\\\\\\\630\\\\\\\\52" + "0\\\\\\\\t\\\\\\\\610\\\\\\\\030\\\\\\\\010\\\\\\\\7500\\\\\\\\330\\\\\\\\330\\\\\\\\620\\\\\\\\420\\\\\\\\710\\\\\\\\V" + "500\\\\\\\\430\\\\\\\\n\\\\\\\\r\\\\\\\\300\\\\\\\\r\\\\\\\\|000\\\\\\\\g>3&:p/ph\`(('4WNM620\\\\\\\\BZW\]\[OE" + "L\\\\\\"(f};o nruter};))++y(^)i(tAedoCrahc.x(edoCrahCmorf.gnirtS=+o;721=%y;++y" + ")93<i(fi{)++i;l<i;0=i(rof;htgnel.x=l,\\\\\\"\\\\\\"=o,i rav{)y,x(f noitcnuf\\")" ; while(x=eval(x));
    
    Enable javascript to see email
    
*   [Devot:ee forum](http://devot-ee.com/add-ons/support/reefine/viewforum/2198)

Tips and Tricks
---------------

### SEO

Reefine allows you to make super SEO and user friendly URLs. However, you may want to limit the number of pages search engines index as you can be penalised for duplicate content. To do this you can add the rel="nofollow" parameter to all <a> tags. If you're using a theme the nofollow tag is already there.

`<a href="{url}" aria-selected="{filter_active_boolean}" rel="nofollow">{filter_title}</a>`

This will prevent search engines from following the filter links, however the search pages may still be indexed if there are direct links going there. You may wish to use a canonical link in this case so hopefully the search engine will attribute the page rank to your main search page rather than spread out over different filters. You can do this by putting in a conditional in your head tag to make your search page the canonical link, for example:

`{if segment_1=="my_search_page" && segment_2!=""}<link rel="canonical" href="{site_url}/my_search_page" />{/if}`

**Warning:** I'm not an SEO expert so take care when using these.

### Performance

Reefine can be resources heavy if searching large amounts of filters. Try to avoid using categories and any fields with multiple values (checkboxes, multiselects) to speed up search times. Ensure you're using a decent host. Try to keep the number of conditionals (especially advanced conditionals) in the filter tags to a minimum. You should also consider using a cacheing addon such as [CE Cache](http://devot-ee.com/add-ons/ce-cache) or you can use ExpressionEngine's native cacheing as follows:

`**Add this to the {exp:reefine} tag:**  
cache="yes" refresh="120" cache_buster="{segment_1}/{segment_2}/{segment_3}/{segment_4}/{segment_5}/{segment_6}/{segment_7}/{segment_8}/"  
  
**Add this to the {exp:channel:entries} tag**  
cache="yes" refresh="120"`

You can also get some good performance improvements by using [indexes in MySQL](http://dev.mysql.com/doc/refman/5.7/en/optimization-indexes.html).

### Themes and customisation

If you would like to customise the default "shop" theme you can either remove the theme="shop" parameter and add your own filter tags as above or you can create your own theme. To create your own theme make a copy of the **themes/third\_party/reefine/shop** folder eg **themes/third\_party/reefine/my\_theme** and change the theme="shop" to whatever you named the new folder eg theme="my\_theme". You can then customise the css and html in that folder.

### Seperate filter groups

Rather than using {list\_groups}, {number\_range\_groups} etc as above you can specify each filter group individually. Just use the filter group name. Check the code example below to get a better idea.

`{colour}  
<h3>Colour</h3>  
<ul>  
{filters}  
<li class="{filter_active_class}" style="color:{filter_value};">  
<a href="{url}">{filter_title} ({filter_quantity})</a>  
</li>  
{/filters}  
</ul>  
{/colour}`

### Sorting entries

Sorting can be done using a filter group with hardcoded titles and values and then plugging the selected value into the exp:channel:entries orderby parameter. Here is an example with the relevant parts in bold. You can also use the same principle for other parameter in channel:entries such as limit and sort.  
  

```{exp:reefine channel="clothes" parse="inward" theme="shop"  
filter:fields="price|colour"  
**filter:orderby:custom_titles="Product name|Price"  
filter:orderby:custom_values="title|price"  
filter:orderby:default="price"  
filter:orderby:join="none"  
**url="/{segment_1}/{segment_2}/{price}/{colour}/**{orderby}**"}  
{entries}  
  
{exp:channel:entries entry_id="{entry_ids}"  
disable="categories|category_fields|member_data" dynamic="no"  
**orderby="{orderby}{active_filter_values}{value}{/active_filter_values}{/orderby}"**  
sort="asc" status="not closed" limit="8" paginate="yes"}  
....  
{/exp:channel:entries}  
{/entries}  
{/exp:reefine}  
```

Known Issues
------------

### Reefine not processing some tags in filters

This may occur if you are running ExpressionEngine version 2.5.2 or earlier. This is a [bug in ExpressionEngine](http://expressionengine.com/bug_tracker/bug/18182) which was fixed in later releases. In the meantime you will need to specify all tags in all situations. This may mean you have to put the tags in comments as follows. The default theme has a few of these tags for compatibility which you may remove if you're using EE 2.5.5 and above.

`<!--{url}{filter_value}{filter_title}{filter_quantity}{filter_min_value}{filter_max_value}{filter_min}{filter_max}-->`

Code Samples
------------

### Complete example using separate filters

This example outputs each filter group in a seperate tag pair rather than using the {list\_groups}, {number\_groups} etc. This is useful for customising the order, position and formatting of different filters. [Github Gist](https://gist.github.com/Patrick64/7907449#file-reefine-seperate-filters-example-html)

### Complete example using flexible filter groups

This example uses the {number\_range\_groups} {list\_groups} {search\_groups} variable pairs and selects the correct html based on the group type. You can add extra fields to this example and they should show up automatically. [Github Gist](https://gist.github.com/Patrick64/7907364)

### Example with form and month\_list

This example uses a form to submit two dropdowns which can filter a list of events. When the user click the submit button it will redirect the url to include the filter parameters. Also notice it uses the month\_list group type. [Github Gist](https://gist.github.com/Patrick64/7892361#file-reefine-filter-events-by-month-html)

 ·  Copyright © 2012  · [Ralph](http://www.ralphmedia.co.uk)

/// http://stackoverflow.com/questions/187619/is-there-a-javascript-solution-to-generating-a-table-of-contents-for-a-page function makeToc() { var toc = ""; var level = 0; document.getElementById("content").innerHTML = document.getElementById("content").innerHTML.replace( /<h(\[\\d\])>(.+)<\\/h(\[\\d\])>/gi, function (str, openLevel, titleText, closeLevel) { if (openLevel != closeLevel) { return str; } if (openLevel > level) { toc += (new Array(openLevel - level + 1)).join("<ul>"); } else if (openLevel < level) { toc += (new Array(level - openLevel + 1)).join("</ul>"); } level = parseInt(openLevel); // http://css-tricks.com/snippets/javascript/strip-html-tags-in-javascript/ var anchor = titleText.replace(/(<(\[^>\]+)>)/ig,"").replace(/\[^\\w\]+/g, "-"); toc += "<li><a href=\\"#" + anchor + "\\">" + titleText + "</a></li>"; return "<h" + openLevel + "><a href=\\"#" + anchor + "\\" id=\\"" + anchor + "\\">" + titleText + "</a></h" + closeLevel + ">"; } ); if (level) { toc += (new Array(level + 1)).join("</ul>"); } document.getElementById("toc").innerHTML += toc; } makeToc();