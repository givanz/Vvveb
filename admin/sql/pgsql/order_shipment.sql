-- Shipment

	CREATE PROCEDURE getByOrder(
		IN order_id INT,
		OUT fetch_all
	)
	BEGIN
		-- shipment
		SELECT *
			FROM order_shipment WHERE order_shipment.order_id = :order_id;
	END

	CREATE PROCEDURE add(
		IN shipment ROW,
		OUT insert_id
	)
	BEGIN
		-- allow only table fields and set defaults for missing values
		:shipment_data  = @FILTER(:shipment, order_shipment)


		INSERT INTO order_shipment

			( @KEYS(:shipment_data) )

	  	VALUES ( :shipment_data ) RETURNING order_shipment_id;

	END
