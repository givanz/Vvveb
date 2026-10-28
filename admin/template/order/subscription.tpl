import(crud.tpl, {"type":"subscription"})

/* lifecycle action buttons carry the subscription id */
[data-v-subscription-action-id]|value = $this->subscription['subscription_id']
