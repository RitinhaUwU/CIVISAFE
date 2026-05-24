import {openDB, type IDBPDatabase} from "idb";

let database: IDBPDatabase<unknown> | null = null;

export async function createDB() {

    if (database !== null) {
        return;
    }

    if (navigator.storage && navigator.storage.persist) {
        const res = await navigator.storage.persist();
        console.debug(`Estado de persistência de dados: ${res}`);
    }

    // Fonte: https://web.dev/learn/pwa/offline-data#creating_and_opening_a_database
    // Using https://github.com/jakearchibald/idb

    // TODO: Incrementar o valor de da versão à medida que se fazem atualizações à estrutura

    database = await openDB('civisafe', 1, {
        upgrade(db, oldVersion, newVersion, transaction) {
            // Switch over the oldVersion, *without breaks*, to allow the database to be incrementally upgraded.
            switch (oldVersion) {
                case 0:
                // Placeholder to execute when database is created (oldVersion is 0)
                case 1:
                    // Create a store of objects
                    const userStore = db.createObjectStore('users', {keyPath: 'id'});
                    // Create an index called `name` based on the `type` property of objects in the store
                    userStore.createIndex('id', 'id');
                    userStore.createIndex('name', 'name');

                    const incidentStatesStore = db.createObjectStore('incidentStates', {keyPath: 'id'});
                    incidentStatesStore.createIndex('id', 'id');
                    incidentStatesStore.createIndex('name', 'name');

                    const incidentPrioritiesStore = db.createObjectStore('incidentPriorities', {keyPath: 'id'});
                    incidentPrioritiesStore.createIndex('id', 'id');
                    incidentPrioritiesStore.createIndex('name', 'name');
                    incidentPrioritiesStore.createIndex('description', 'description');

                    const incidentTypesStore = db.createObjectStore('incidentTypes', {keyPath: 'id'});
                    incidentTypesStore.createIndex('id', 'id');
                    incidentTypesStore.createIndex('code', 'code');
                    incidentTypesStore.createIndex('species', 'species');
                    incidentTypesStore.createIndex('type', 'type');

                    const entityTypesStore = db.createObjectStore('entityTypes', {keyPath: 'id'});
                    entityTypesStore.createIndex('id', 'id');
                    entityTypesStore.createIndex('name', 'name');

                    const entitiesStore = db.createObjectStore('entities', {keyPath: 'id'});
                    entitiesStore.createIndex('id', 'id');
                    entitiesStore.createIndex('name', 'name');

                    const volunteersStore = db.createObjectStore('volunteers', {keyPath: 'id'});
                    volunteersStore.createIndex('id', 'id');
                    volunteersStore.createIndex('name', 'name');
                    volunteersStore.createIndex('team_identification', 'team_identification');
                    volunteersStore.createIndex('classification', 'classification');
                    volunteersStore.createIndex('has_accommodation', 'has_accommodation');
                    volunteersStore.createIndex('has_meal', 'has_meal');
            }
        }
    });
}

export async function storeData(objectStore: string, object: object) {
    if (database === null) {
        await createDB()
    }

    const tx = database.transaction(objectStore, 'readwrite');
    const store = tx.objectStore(objectStore);

    await store.put(object);
    await tx.done;
}

export async function retrieveData(objectStore: string, index: number = -1) {
    if (database === null) {
        await createDB()
    }

    const tx = database.transaction(objectStore, 'readonly');
    const store = tx.objectStore(objectStore);

    if (index <= 0) {
        return await store.getAll();
    } else {
        return await store.get(index);
    }
}
