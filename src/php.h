typedef uint64_t zend_ulong;
typedef int64_t zend_long;
typedef int64_t zend_off_t;
typedef unsigned long sigset_t;

typedef struct _zend_object_handlers zend_object_handlers;
typedef unsigned char zend_uchar;
typedef struct _zend_array zend_array;
typedef struct _zend_object zend_object;
typedef struct _zend_resource zend_resource;
typedef struct _zend_reference zend_reference;
typedef struct _zval_struct zval;
typedef struct _zend_ast_ref    zend_ast_ref;
typedef struct _zend_ast        zend_ast;
typedef struct _zend_class_entry     zend_class_entry;
typedef union  _zend_function        zend_function;
typedef struct _zend_array HashTable;
typedef void (*dtor_func_t)(zval *pDest);

typedef struct _php_stream php_stream;

typedef struct _zend_refcounted_h {
    uint32_t         refcount;                      /* reference counter 32-bit */
    union {
            uint32_t type_info;
    } u;
} zend_refcounted_h;
typedef struct _zend_string {
    zend_refcounted_h gc;
    zend_ulong        h;                /* hash value */
    size_t            len;
    char              val[1];
} zend_string;
typedef struct _zend_refcounted {
    zend_refcounted_h gc;
} zend_refcounted;
struct _zend_resource {
    zend_refcounted_h gc;
    int               handle; // TODO: may be removed ???
    int               type;
    void             *ptr;
};
typedef struct _zend_property_info zend_property_info;

typedef union {
        zend_property_info *ptr;
        uintptr_t list;
} zend_property_info_source_list;


struct _zval_struct {
    union {
        zend_long         lval;             /* long value */
        double            dval;             /* double value */
        zend_refcounted  *counted;
        zend_string      *str;
        zend_array       *arr;
        zend_object      *obj;
        zend_resource    *res;
        zend_reference   *ref;
        zend_ast_ref     *ast;
        zval             *zv;
        void             *ptr;
        zend_class_entry *ce;
        zend_function    *func;
        struct {
            uint32_t w1;
            uint32_t w2;
        } ww;
    } value;
    union {
        struct {
                zend_uchar    type;         /* active type */
                zend_uchar    type_flags;
                zend_uchar    const_flags;
                zend_uchar    reserved;     /* call info for EX(This) */
        } v;
        uint32_t type_info;
    } u1;
    union {
        uint32_t     var_flags;
        uint32_t     next;                 /* hash collision chain */
        uint32_t     cache_slot;           /* literal cache slot */
        uint32_t     lineno;               /* line number (for ast nodes) */
        uint32_t     num_args;             /* arguments number for EX(This) */
        uint32_t     fe_pos;               /* foreach position */
        uint32_t     fe_iter_idx;          /* foreach iterator index */
    } u2;
};
struct _zend_ast_ref {
        zend_refcounted_h gc;
        /*zend_ast        ast; zend_ast follows the zend_ast_ref structure */
};
struct _zend_reference {
    zend_refcounted_h              gc;
    zval                           val;
    zend_property_info_source_list sources;
};
struct _zend_object {
    zend_refcounted_h gc;
    uint32_t          handle; // TODO: may be removed ???
    zend_class_entry *ce;
    const zend_object_handlers *handlers;
    HashTable        *properties;
    zval              properties_table[1];
};
typedef struct _Bucket {
    zval              val;
    zend_ulong        h;                /* hash value (or numeric index)   */
    zend_string      *key;              /* string key or NULL for numerics */
} Bucket;

typedef struct _zend_array {
    zend_refcounted_h gc;
    union {
            struct {
                zend_uchar    flags;
                zend_uchar    _unused;
                zend_uchar    nIteratorsCount;
                zend_uchar    _unused2;
            } v;
            uint32_t flags;
    } u;
    uint32_t          nTableMask;
    Bucket           *arData;
    uint32_t          nNumUsed;
    uint32_t          nNumOfElements;
    uint32_t          nTableSize;
    uint32_t          nInternalPointer;
    zend_long         nNextFreeElement;
    dtor_func_t       pDestructor;
};

typedef struct _php_stream_filter_chain {
        void *head, *tail;

        /* Owning stream */
        php_stream *stream;
} php_stream_filter_chain;

struct _php_stream  {
    void *ops;
    void *abstract;                 /* convenience pointer for abstraction */

    php_stream_filter_chain readfilters, writefilters;

    void *wrapper; /* which wrapper was used to open the stream */
    void *wrapperthis;              /* convenience pointer for a instance of a wrapper */
    zval wrapperdata;               /* fgetwrapperdata retrieves this */

    uint8_t is_persistent:1;
    uint8_t in_free:2;                      /* to prevent recursion during free */
    uint8_t eof:1;
    uint8_t __exposed:1;    /* non-zero if exposed as a zval somewhere */

    /* so we know how to clean it up correctly.  This should be set to
        * PHP_STREAM_FCLOSE_XXX as appropriate */
    uint8_t fclose_stdiocast:2;

    uint8_t fgetss_state;           /* for fgetss to handle multiline tags */

    char mode[16];                  /* "rwb" etc. ala stdio */

    uint32_t flags; /* PHP_STREAM_FLAG_XXX */

    zend_resource *res;             /* used for auto-cleanup */
    void *stdiocast;    /* cache this, otherwise we might leak! */
    char *orig_path;

    zend_resource *ctx;

    /* buffer */
    zend_off_t position; /* of underlying stream */
    unsigned char *readbuf;
    size_t readbuflen;
    zend_off_t readpos;
    zend_off_t writepos;

    /* how much data to read when filling buffer */
    size_t chunk_size;
    struct _php_stream *enclosing_stream; /* this is a private stream owned by enclosing_stream */
}; /* php_stream */

typedef struct {
	void *file;
	int fd;					/* underlying file descriptor */
	unsigned is_process_pipe:1;	/* use pclose instead of fclose */
	unsigned is_pipe:1;			/* don't try and seek */
	unsigned cached_fstat:1;	/* sb is valid */
	unsigned is_pipe_blocking:1; /* allow blocking read() on pipes, currently Windows only */
	unsigned no_forced_fstat:1;  /* Use fstat cache even if forced */
	unsigned _reserved:28;

	int lock_flag;			/* stores the lock state */
	zend_string *temp_name;	/* if non-null, this is the path to a temporary file that
							 * is to be deleted when the stream is closed */
	char last_op;
	char *last_mapped_addr;
	size_t last_mapped_len;

	void * sb;
} php_stdio_stream_data;

typedef union epoll_data {
    void *ptr;
    int fd;
    uint32_t u32;
    uint64_t u64;
} epoll_data_t;

typedef struct epoll_event {
    uint32_t events; /* Epoll events */
    epoll_data_t data; /* User data variable */
} epoll_event;
int epoll_create(int size);
int epoll_create1 (int __flags);
int epoll_ctl(int epfd, int op, int fd, struct epoll_event *event);
int epoll_wait(int epfd, struct epoll_event * events, int maxevents, int timeout);
int epoll_pwait(int epfd, struct epoll_event *events,int maxevents, int timeout,const sigset_t *sigmask);

int errno;
char *strerror(int errno);

zend_array *zend_rebuild_symbol_table(void);
HashTable*  zend_array_dup(HashTable *source);