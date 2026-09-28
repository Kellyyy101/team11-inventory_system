# ENDPOINT_LIST.md
GROUP 11

Kelly Wambugu – 220001
Victor Amukoye – 220621
Jayden Mwendo – 220774
Linet Weru – 222170

Github link: https://github.com/Kellyyy101/-team-11--inventory_system

| Method | Path | Purpose | Maps to Need |
|---|---|---|---|
| GET | /found-items | Return a list of found items so users can search for items they previously reported as lost. | LiteStock needs to read a list of found items in order to allow users to search for items that they have previously reported as lost. |
| GET | /found-items?search={term} | Return a filtered list of found items matching the user's search term. | LiteStock needs to read a list of found items in order to allow users to search for items that they have previously reported as lost. |
| GET | /found-items/{id} | Return the details of a specific found item so users can compare its identifying information with the item they lost. | LiteStock needs to read the details of a found item in order to allow users to compare the item's identifying information with the item they lost. |
| GET | /found-items/{id}/images | Return the images associated with a specific found item so users can visually confirm whether it matches the item they lost. | LiteStock needs to read images of found items in order to allow users to visually confirm that a found item matches the item they lost before travelling to retrieve it. |
| GET | /found-items/{id}/location | Return the retrieval location of a specific found item so users know where to go to collect it. | LiteStock needs to read the retrieval location of a found item in order to allow users to know where to go to collect their item once they have identified it. |
| POST | /found-items | Record a recently found item, including its identifying details, image and retrieval location. | A found item can be recorded together with its details, images, and retrieval location. |