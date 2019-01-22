ApiExtension Magento1 Extension
=============================

This extension works with Magento 1.9.x and creates the endpoints for ApiExtension to allow the sync processes to run. 



Install
-------

Install via composer or Magento Connect

## Customers, subscribers and customer addresses

### Customer

```
DELETE /customers/{id}
```
Description: Allows you to delete an existing customer by ID.

Notes: Admin only can delete a customer.

```
GET /customers/{id}
```
Description: Allows you to retrieve information on an existing customer.

Notes: The list of attributes that will be returned for customers is configured in the Magento Admin Panel. The Customer user type has access only to his/her own information. Also, Admin can add additional non-system customer attributes by selecting Customers > Attributes > Manage Customer Attributes. If these attributes are set as visible on frontend, they will be returned in the response. Also, custom attributes will be returned in the response only after the customer information is updated in the Magento Admin Panel or the specified custom attribute is updated via API (see the PUT method below). Please note that managing customer attributes is available only in Magento Enterprise Edition.

```
POST /customers
```
Description: Allows you to create a new customer.

Notes: Admin only can create a customer.

```
PUT /customers/{id}
```
Description: Allows you to update an existing customer.

Notes: The list of attributes that will be updated for customer is configured in the Magento Admin Panel. The Customer user type has access only to his/her own information.

```
GET /customers/search
```
GET /customers/search
Search criteria supported:
- currentPage and pageSize,
- filter by field, value, condition type (e.g. field "email" equals example@example.com)



```
DELETE /customers/addresses/{addressId}
PUT /customers/{id}/addresses
GET /customers/{id}/addresses
POST /customers/{id}/addresses
```

