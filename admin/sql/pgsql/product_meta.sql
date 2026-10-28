-- product_content_meta

	-- get one product_content_meta

	CREATE PROCEDURE get(
		IN product_id INT,
		IN namespace CHAR,
		IN key CHAR,
		
		OUT fetch_one
	)
	BEGIN

            SELECT value
            FROM product_content_meta AS _
		WHERE _."key" = :key 
		
		@IF !empty(:product_id) 
		THEN 
			AND _.product_id = :product_id
		END @IF	

		@IF !empty(:namespace) 
		THEN 
			AND _.namespace = :namespace
		END @IF	

		
	END
    
	CREATE PROCEDURE set(
		IN product_id INT,
		IN language_id INT,
		IN namespace CHAR,
		IN key CHAR,
		IN value CHAR,
		
		OUT fetch_one
	)
	BEGIN

        INSERT INTO product_content_meta
            (product_id, namespace, language_id, "key", value )
        
        VALUES (:product_id, :namespace, :language_id, :key, :value )
        
		ON CONFLICT ("product_id", "language_id", "namespace", "key") DO UPDATE SET "value" = :value
		RETURNING product_content_meta_id;
		
	END


    CREATE PROCEDURE getMulti(
		IN product_id INT,
		IN language_id INT,
		IN namespace CHAR,
		IN key ARRAY,
		OUT fetch_all
	)
	BEGIN

            SELECT product_id, namespace, "key", value
            FROM product_content_meta AS _
		WHERE 1 = 1
		
		@IF !empty(:product_id) 
		THEN 
			AND _.product_id = :product_id
		END @IF	

		@IF !empty(:namespace) 
		THEN 
			AND _.namespace = :namespace
		END @IF	
		
		@IF !empty(:language_id) 
		THEN 
			AND _.language_id = :language_id
		END @IF	
		
		@IF !empty(:key) 
		THEN 
			AND _."key" IN (:key)
		END @IF	
;
		
	END    
    
    
	CREATE PROCEDURE setMulti(
		IN product_id INT,
		IN language_id INT,
		IN namespace CHAR,
		IN meta ROWS,
		OUT insert_id
	)
	BEGIN
		:meta = @FILTER(:meta, product_content_meta, false, true)

		@EACH(:meta)
			INSERT INTO product_content_meta

				( product_id, language_id, namespace, @KEYS(:each) )

			VALUES ( :product_id, :language_id, :namespace, :each )

			ON CONFLICT("product_id", "language_id", "namespace", "key") DO UPDATE SET "value" = :each.value;
	END
    
	CREATE PROCEDURE delete(
		IN product_id INT,
		IN namespace CHAR,
		IN key ARRAY
		OUT affected_rows
	)
	BEGIN

        	DELETE FROM 
			product_content_meta 
		WHERE 1 = 1
		
		@IF !empty(:namespace) 
		THEN 
			AND namespace = :namespace
		END @IF	

		@IF !empty(:key) 
		THEN 
			AND "key" IN (:key)
		END @IF	

		@IF !empty(:product_id) 
		THEN 
			AND product_id = :product_id
		END @IF	
		;		
		
	END    
