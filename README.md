# Scandipwa_SalesGraphQl

Magneto 2 Sales related functionality extension

## What`s inside?

This module provides GraphQl customization to exist one endpoints.

### Customization endpoints 

* Allows filtering orders by entity_id
```graphql
input CustomerOrdersFilterInput {
    entity_id: FilterStringTypeInput @doc(description: "Filters by order entity id.")
}
```
* Returns boolean to define if order can be reordered and rss_link exist for it

```graphql
type CustomerOrder {
    can_reorder: Boolean! @doc(description: "Defines if order can be reordered")
    rss_link: String @doc(description: "Represents rss link to subscribe on order status")
}
```

* Return row subtotal price 

```graphql
interface OrderItemInterface {
   row_subtotal: Money! @doc(description: "The row subtotal price, including selected options")
}
```

* Extend order products selected and entered options to return
  also downloadable links, bundle options and files
