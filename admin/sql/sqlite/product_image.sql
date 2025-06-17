-- product_image

	-- get all product_image

	CREATE PROCEDURE getAll(
		IN product_id INT,
		IN product_image_id ARRAY,
		
		OUT fetch_all,
	)
	BEGIN

		SELECT *
			FROM product_image
		WHERE 1 = 1
		
		@IF !empty(:product_id) 
		THEN 
			AND product_id = :product_id
		END @IF	
		
		@IF !empty(:product_image_id) 
		THEN 
			AND product_image_id IN (:product_image_id)
		END @IF	

		
	END

	-- get one product_image

	CREATE PROCEDURE get(
		IN product_id INT,
		IN product_image_id INT,
		
		OUT fetch_row,
	)
	BEGIN

		SELECT *
			FROM product_image AS _
		WHERE _.`key` = :key 
		
		@IF !empty(:product_id) 
		THEN 
			AND _.product_id = :product_id
		END @IF	
		
		@IF !empty(:namespace) 
		THEN 
			AND _.namespace = :namespace
		END @IF		

		
	END
    
	CREATE PROCEDURE add(
		IN product_id INT,
		IN namespace CHAR,
		IN key CHAR,
		IN value CHAR,
		
		OUT insert_id
	)
	BEGIN

        INSERT INTO product_image
            (product_id, namespace, `key`, value )
        
        VALUES (:product_id, :namespace, :key, :value )
        
        ON DUPLICATE KEY 
            UPDATE value = values(value);
		
	END

	CREATE PROCEDURE edit(
		IN product_image ARRAY,
		IN product_image_id INT,
		OUT affected_rows
	)
	BEGIN
		-- allow only table fields and set defaults for missing values
		@FILTER(:product_image, product_image)

		UPDATE product_image 
			
			SET  @LIST(:product_image) 
			
		WHERE product_image_id = :product_image_id
	 
	END

	CREATE PROCEDURE delete(
		IN product_id INT,
		IN namespace CHAR,
		IN key ARRAY
	)
	BEGIN

        DELETE FROM 
			product_image 
		WHERE 1 = 1
		
		@IF !empty(:namespace) 
		THEN 
			AND namespace = :namespace
		END @IF	

		@IF !empty(:key) 
		THEN 
			AND `key` IN (:key)
		END @IF	

		@IF !empty(:product_id) 
		THEN 
			AND product_id = :product_id
		END @IF	
		;		
		
	END    
