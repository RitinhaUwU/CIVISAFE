# S3-Compatible Storage (S3/MinIO/RustFS)

Devem ser configurados os seguintes buckets e permissões no S3:

 - `misc`
   - Acesso `write` anónimo em `/` 
   
 - ``

```dotenv
AWS_ENDPOINT=http://minio:9000
AWS_ACCESS_KEY_ID=
AWS_SECRET_ACCESS_KEY=
AWS_DEFAULT_REGION=us-east-1
AWS_USE_PATH_STYLE_ENDPOINT=true
```
