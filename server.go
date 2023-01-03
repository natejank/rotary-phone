package main

import (
	"fmt"
	"log"
	"net/http"
	"os"
	"time"
)

func page_header(title string) string {
	return "<!DOCTYPE html>" +
			"<head>" +
			"	<meta charset=\"utf-8\">" +
			"	<link rel=\"stylesheet\" href=\"style.css\">" +
			"	<title>" + title + "</title>" +
			"</head>"

}

// URL Handler function
func handle_func(response http.ResponseWriter, request *http.Request) {
	log.Printf("%q: %q\n", request.URL.Path, request.Method)
	fmt.Fprintf(response, "<p>Hello <b>%q</b></p>", request.URL.Path)
}

func main() {
	args := os.Args[1:]
	var port string

	switch len(args) {
	case 0:
		port = ":80"
		break
	case 1:
		port = ":" + args[0]
		break
	default:
		log.Fatal("Invalid usage!  server [port]")
	}

	// handle paths
	mux := http.NewServeMux()
	mux.HandleFunc("/", handle_func)

	server := &http.Server{
		Addr: port,
		Handler: mux,
		IdleTimeout: time.Minute,
		ReadTimeout: 10 * time.Second,
		WriteTimeout: 30 * time.Second,
	}
	log.Printf("Serving on %s", server.Addr)
	err := server.ListenAndServe()
	log.Fatal(err)
}