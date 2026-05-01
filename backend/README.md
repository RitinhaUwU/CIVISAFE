# Dev Environment

Backend:
```shell
php artisan serve
php artisan reverb:start
php artisan queue:work
php artisan queue:work --queue=notifications
php artisan queue:work --queue=imports
```

Frontend:
```shell
npm run dev
```

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

# Filas

- Default: Fila padrão do Laravel
- imports: fila onde são importados ficheiros e afins
- notifications: Fila de lançamento de notificações. Estão separadas para não serem bloqueadas por outros processos
