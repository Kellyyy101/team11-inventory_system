# ENDPOINT_LIST.md
GROUP 11

Kelly Wambugu – 220001
Victor Amukoye – 220621
Jayden Mwendo – 220774
Linet Weru – 222170

Github link: https://github.com/Kellyyy101/team11-inventory_system

| Method | Path | Purpose| Maps to Need |
| ----- | ----- | ----- | -----|

| GET    | `/api/providers/{provider_id}`  | Return the details of a specific registered provider so QuickFundi can display the provider's basic information to clients.| QuickFundi needs to retrieve registered provider details from LiteStock in order to display basic information about service providers to clients.|

| GET    | `/api/products?provider_id={provider_id}`  | Return a list of products or services associated with a specific provider so QuickFundi can show clients what the provider offers.| QuickFundi needs to retrieve product or service information from LiteStock in order to help clients understand the services offered by a registered provider. |

| GET    | `/api/sales?provider_id={provider_id}`     | Return sales information associated with a specific provider so QuickFundi can review the provider's recorded sales and activity.  | QuickFundi needs to retrieve sales information from LiteStock in order to help determine the provider's activity and service history.|

| GET    | `/api/inventory?provider_id={provider_id}` | Return inventory information for a specific provider so QuickFundi can determine whether the provider has relevant products or materials available. | QuickFundi needs to retrieve inventory information from LiteStock in order to determine whether a provider has the necessary products or materials available for a requested service. |

| POST   | `/api/providers` | Record a new provider in LiteStock when the provider is not already registered in the system.| QuickFundi needs to submit a service provider record to LiteStock in order to register a provider who is not yet present in the LiteStock system. |
