Kelly Wambugu – 220001
Victor Amukoye – 220621


LiteStock API Needs Statements
1.	LiteStock needs to read a list of found items in order to allow users to search for items that they have previously reported as lost.
2.	LiteStock needs to read the details of a found item in order to allow users to compare the item's identifying information with the item they lost.
3.	LiteStock needs to read images of found items in order to allow users to visually confirm that a found item matches the item they lost before travelling to retrieve it.
4.	LiteStock needs to read the retrieval location of a found item in order to allow users to know where to go to collect their item once they have identified it.
Non-functional details
API Need	Freshness	Volume	Authentication
Read list of found items	Near real-time or updated whenever a new item is recorded	Potentially several times per user search	Authentication may be required
Read item details	Current information when the item is searched	Several requests depending on searches	Authentication may be required
Read item images	Should reflect the latest available images	Usually requested when viewing a specific item	Authentication may be required
Read retrieval location	Should be current to prevent users going to an incorrect location	Usually requested when viewing a specific item	Authentication may be required
Reflection
The interview with the upstream Lost & Found partner showed that their system provides more than simply a record of whether an item has been found. A found item can be recorded together with its details, images and the location where it is being kept for retrieval. What was particularly useful was learning that a person can search for an item they lost, view its information and images and use the retrieval location to collect it. This means LiteStock can consume specific information from the Lost & Found system rather than requiring access to the entire system. It also showed that the API needs should focus on the information that supports the user's retrieval process rather than exposing unnecessary functionality.

