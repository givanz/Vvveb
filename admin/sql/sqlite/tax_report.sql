-- Tax report: collected tax grouped by rate title and ship-to country,
-- plus refunded tax totals. Filing support: collected minus refunded.

	CREATE PROCEDURE collected(
		IN date_from CHAR,
		IN date_to CHAR,
		IN order_status_id ARRAY,
		IN country_id INT,
		IN site_id INT,
		OUT fetch_all
	)
	BEGIN
		-- order_total
		SELECT order_total.title AS name, country.name AS country, SUM(order_total.value) AS total, COUNT(DISTINCT order_total.order_id) AS orders
			FROM order_total
			LEFT JOIN `order` ON (order_total.order_id = `order`.order_id)
			LEFT JOIN country ON (`order`.shipping_country_id = country.country_id)
		WHERE order_total.`key` = 'tax'

		@IF !empty(:date_from)
		THEN
			AND `order`.created_at >= :date_from
		END @IF

		@IF !empty(:date_to)
		THEN
			AND `order`.created_at <= :date_to
		END @IF

		@IF !empty(:order_status_id)
		THEN
			AND `order`.order_status_id IN (:order_status_id)
		END @IF

		@IF !empty(:country_id)
		THEN
			AND `order`.shipping_country_id = :country_id
		END @IF

		@IF isset(:site_id) AND :site_id != ""
		THEN
			AND `order`.site_id = :site_id
		END @IF

		GROUP BY order_total.title, country.name
		ORDER BY total DESC;
	END

	CREATE PROCEDURE refunded(
		IN date_from CHAR,
		IN date_to CHAR,
		OUT fetch_one
	)
	BEGIN
		SELECT SUM(tax_amount) FROM order_transaction
		WHERE kind = 'refund' AND status = 'succeeded'

		@IF !empty(:date_from)
		THEN
			AND created_at >= :date_from
		END @IF

		@IF !empty(:date_to)
		THEN
			AND created_at <= :date_to
		END @IF
		;
	END
