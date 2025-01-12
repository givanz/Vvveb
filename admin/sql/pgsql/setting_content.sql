-- Setting content

	-- get one setting content


    CREATE PROCEDURE get(
		IN site_id INT,
		IN namespace CHAR,
		IN key CHAR,
		IN language_id INT,
		
		OUT fetch_one
	)
	BEGIN

		SELECT `key`, value
			FROM setting_content AS _
		WHERE 1 = 1
		
		@IF !empty(:site_id) 
		THEN 
			AND _.site_id = :site_id
		END @IF			
		
		@IF !empty(:namespace) 
		THEN 
			AND _.namespace = :namespace
		END @IF		
		
		@IF !empty(:key) 
		THEN 
			AND _.`key` = :key
		END @IF	

		@IF !empty(:language_id) 
		THEN 
			AND _.language_id = :language_id
		END @IF	
		;
		
	END    
    
	CREATE PROCEDURE set(
		IN site_id INT,
		IN namespace CHAR,
		IN key CHAR,
		IN value CHAR,
		IN language_id INT,
		
		OUT insert_id
	)
	BEGIN

        INSERT INTO setting_content
            (`namespace`,`key`, value, site_id, language_id)
        
        VALUES ( :key, :value, :site_id , :language_id )
        
        ON CONFLICT("site_id", "language_id", "namespace", "key") DO UPDATE SET "value" = :value;
		
	END
    
	CREATE PROCEDURE getMulti(
		IN site_id INT,
		IN namespace CHAR,
		IN key ARRAY,
		IN language_id INT,
		
		OUT fetch_all
	)
	BEGIN

		SELECT site_id, namespace, "key", value, language_id
			FROM setting_content AS _
		WHERE 1 = 1
		
		@IF !empty(:site_id) 
		THEN 
			AND _.site_id = :site_id
		END @IF			
		
		@IF !empty(:namespace) 
		THEN 
			AND _.namespace = :namespace
		END @IF	
		
		@IF !empty(:key) 
		THEN 
			AND _."key" IN (:key)
		END @IF	

		@IF !empty(:language_id) 
		THEN 
			AND _.language_id = :language_id
		END @IF	
		;
		
	END    
    
    
	CREATE PROCEDURE setMulti(
		IN site_id INT,
		IN meta ARRAY,
		
		OUT insert_id
	)
	BEGIN
	
	   :meta = @FILTER(:meta, setting_content, false, true)
	
		@EACH(:meta) 
			INSERT INTO setting_content 
		
				( @KEYS(:each), site_id)
			
			VALUES ( :each, :site_id )   
			ON CONFLICT("site_id", "language_id", "namespace", "key") DO UPDATE SET "value" = :each.value;
		
	END

	CREATE PROCEDURE delete(
		IN site_id INT,
		IN namespace CHAR,
		IN key ARRAY,
		IN language_id INT,
		OUT affected_rows
	)
	BEGIN

        DELETE FROM 
            setting_content 
		WHERE 1 = 1  
		
		@IF !empty(:namespace) 
		THEN 
			AND namespace = :namespace
		END @IF	

		@IF !empty(:key) 
		THEN 
			AND "key" IN (:key)
		END @IF	

		@IF !empty(:language_id) 
		THEN 
			AND language_id = :language_id
		END @IF	

		@IF !empty(:site_id) 
		THEN 
			AND site_id = :site_id
		END @IF	
		;		
	END    
